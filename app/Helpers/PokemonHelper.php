<?php

declare(strict_types=1);

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use App\Models\Pokemon;

class PokemonHelper
{
    public static function getPokemon(int|string $id): ?array
    {
        // 0. Verificar caché interna primero (Laravel Cache)
        $cacheKey = "pokemon_{$id}_full_v2";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 1. Verificar persistencia en BD (Normalizada)
        $pokemonModel = \App\Models\Pokemon::with(['types', 'stats', 'moves', 'sprite', 'cry'])
            ->where('api_id', $id)
            ->first();

        if ($pokemonModel) {
            $data = self::formatPokemonModel($pokemonModel);
            Cache::put($cacheKey, $data, 3600);
            return $data;
        }

        // 2. Obtener de la API si no está en BD
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon/{$id}");
            $data = json_decode($response->getBody()->getContents(), true);

            // Transacción de BD para asegurar integridad de datos
            $pokemonModel = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $id) {
                // A. Crear/Actualizar Pokémon Base
                $pokemon = \App\Models\Pokemon::updateOrCreate(
                ['api_id' => $data['id']],
                [
                    'name' => $data['name'],
                    'base_experience' => $data['base_experience'],
                    'height' => $data['height'],
                    'weight' => $data['weight'],
                ]
                );

                // B. Types
                $pokemon->types()->detach();
                foreach ($data['types'] as $t) {
                    $type = \App\Models\Type::firstOrCreate(['name' => $t['type']['name']]);
                    $pokemon->types()->attach($type->id, ['slot' => $t['slot']]);
                }

                // C. Stats
                $pokemon->stats()->detach();
                foreach ($data['stats'] as $s) {
                    $stat = \App\Models\Stat::firstOrCreate(['name' => $s['stat']['name']]);
                    $pokemon->stats()->attach($stat->id, ['base_value' => $s['base_stat']]);
                }

                // D. Sprites
                \App\Models\PokemonSprite::updateOrCreate(
                ['pokemon_id' => $pokemon->id],
                [
                    'front_default' => $data['sprites']['front_default'],
                    'official_artwork' => $data['sprites']['other']['official-artwork']['front_default'] ?? null,
                    'front_shiny' => $data['sprites']['front_shiny'],
                    'back_default' => $data['sprites']['back_default'],
                ]
                );

                // E. Cries
                if (isset($data['cries'])) {
                    \App\Models\PokemonCry::updateOrCreate(
                    ['pokemon_id' => $pokemon->id],
                    [
                        'latest' => $data['cries']['latest'] ?? null,
                        'legacy' => $data['cries']['legacy'] ?? null,
                    ]
                    );
                }

                // F. Moves (Optimized Bulk Operation)
                $movesToAttach = [];
                // Extract all move names
                $moveNames = array_map(fn($m) => $m['move']['name'], $data['moves']);

                // Fetch existing moves
                $existingMoves = \App\Models\Move::whereIn('name', $moveNames)->get()->keyBy('name');

                // Identify missing moves
                $missingMoves = [];
                foreach ($moveNames as $name) {
                    if (!$existingMoves->has($name)) {
                        $missingMoves[] = ['name' => $name, 'created_at' => now(), 'updated_at' => now()];
                    }
                }

                // Insert missing moves in bulk if any
                if (!empty($missingMoves)) {
                    \App\Models\Move::insert($missingMoves);
                    // Fetch again to get IDs including valid ones
                    $existingMoves = \App\Models\Move::whereIn('name', $moveNames)->get()->keyBy('name');
                }

                foreach ($data['moves'] as $m) {
                    $moveName = $m['move']['name'];

                    if (!$existingMoves->has($moveName))
                        continue;

                    $moveDB = $existingMoves->get($moveName);

                    // Find level-up data
                    $level = 0;
                    $method = 'machine';

                    foreach ($m['version_group_details'] as $vgd) {
                        if ($vgd['move_learn_method']['name'] === 'level-up') {
                            $level = $vgd['level_learned_at'];
                            $method = 'level-up';
                            break;
                        }
                    }

                    if ($method !== 'level-up' && !empty($m['version_group_details'])) {
                        $vgd = $m['version_group_details'][0];
                        $level = $vgd['level_learned_at'];
                        $method = $vgd['move_learn_method']['name'];
                    }

                    $movesToAttach[$moveDB->id] = [
                        'level_learned_at' => $level,
                        'learn_method' => $method
                    ];
                }
                $pokemon->moves()->sync($movesToAttach);

                return $pokemon;
            });

            // Cargar evolución para obtener relaciones
            $pokemonModel->load(['types', 'stats', 'moves', 'sprite', 'cry']);
            $formatted = self::formatPokemonModel($pokemonModel);

            // Agregar Evoluciones
            $formatted['evolutions'] = self::fetchEvolutions($id, $client);

            Cache::put($cacheKey, $formatted, 3600);
            return $formatted;

        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PokemonHelper::getPokemon($id) error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private static function formatPokemonModel($pokemon)
    {
        return [
            'id' => $pokemon->api_id,
            'name' => ucfirst($pokemon->name),
            'image' => $pokemon->sprite->official_artwork ?? $pokemon->sprite->front_default ?? "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$pokemon->api_id}.png",
            'types' => $pokemon->types->pluck('name')->toArray(),
            'stats' => $pokemon->stats->mapWithKeys(fn($s) => [$s->name => $s->pivot->base_value])->toArray(),
            'move_names' => $pokemon->moves->pluck('name')->toArray(), // Compatibilidad hacia atrás
            'moves_detailed' => $pokemon->moves->map(fn($m) => [
            'name' => $m->name,
            'level' => $m->pivot->level_learned_at,
            'method' => $m->pivot->learn_method
            ])->sortBy('level')->values()->toArray(),
            'cries' => $pokemon->cry ? ['latest' => $pokemon->cry->latest, 'legacy' => $pokemon->cry->legacy] : [],
            'evolutions' => [], // Se llena por separado si es necesario o cached
            'base_experience' => $pokemon->base_experience,
            'height' => $pokemon->height,
            'weight' => $pokemon->weight,
        ];
    }

    private static function fetchEvolutions($id, $client)
    {
        try {
            // ... (API calls)
            $response = $client->get("https://pokeapi.co/api/v2/pokemon/{$id}");
            $apiData = json_decode($response->getBody()->getContents(), true);
            $speciesResponse = $client->get($apiData['species']['url']);
            $speciesData = json_decode($speciesResponse->getBody()->getContents(), true);
            $evoResponse = $client->get($speciesData['evolution_chain']['url']);
            $evoData = json_decode($evoResponse->getBody()->getContents(), true);
            return self::parseEvolutionChain($evoData['chain']);
        }
        catch (\Exception $e) {
            return [];
        }
    }

    private static function parseEvolutionChain($chain)
    {
        $evolutions = [];
        $current = $chain;

        do {
            $parts = explode('/', rtrim($current['species']['url'], '/'));
            $id = end($parts);

            $evolutions[] = [
                'id' => $id,
                'name' => ucfirst($current['species']['name']),
                'image' => "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$id}.png"
            ];

            if (isset($current['evolves_to'][0])) {
                $current = $current['evolves_to'][0];
            }
            else {
                $current = null;
            }
        } while ($current);

        return $evolutions;
    }

    public static function getMoveDetails($moveName)
    {
        $cacheKey = "move_{$moveName}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client(['timeout' => 5, 'connect_timeout' => 3]);

        try {
            $response = $client->get("https://pokeapi.co/api/v2/move/{$moveName}");
            $data = json_decode($response->getBody()->getContents(), true);

            $nameEs = $moveName;
            foreach ($data['names'] ?? [] as $nameEntry) {
                if ($nameEntry['language']['name'] === 'es') {
                    $nameEs = $nameEntry['name'];
                    break;
                }
            }

            $statusEffect = null;
            $statusChance = 0;
            if (!empty($data['meta'])) {
                $ailment = $data['meta']['ailment']['name'] ?? 'none';
                if ($ailment !== 'none') {
                    $statusEffect = $ailment;
                    $statusChance = $data['meta']['ailment_chance'] ?? 0;
                }
            }

            $result = [
                'name' => $moveName,
                'name_es' => $nameEs,
                'power' => $data['power'],
                'accuracy' => $data['accuracy'],
                'pp' => $data['pp'],
                'type' => $data['type']['name'],
                'damage_class' => $data['damage_class']['name'],
                'priority' => $data['priority'] ?? 0,
                'status_effect' => $statusEffect,
                'status_chance' => $statusChance,
            ];

            Cache::put($cacheKey, $result, 3600);
            return $result;
        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PokemonHelper::getMoveDetails($moveName) error: " . $e->getMessage());
            return null;
        }
    }

    public static function selectBattleMoves($pokemonId, $count = 4, $level = 50)
    {
        $pokemon = self::getPokemon($pokemonId);
        if (!$pokemon || empty($pokemon['move_names'])) {
            return self::getDefaultMoves($pokemon['types'] ?? ['normal']);
        }

        $pokemonTypes = $pokemon['types'] ?? ['normal'];

        // Usar movimientos detallados si están disponibles (nueva lógica)
        $candidates = [];
        if (!empty($pokemon['moves_detailed'])) {
            foreach ($pokemon['moves_detailed'] as $m) {
                // Lógica: Permitir movimientos aprendidos por nivel <= nivel actual
                if ($m['method'] === 'level-up' && $m['level'] <= $level) {
                    $candidates[] = $m['name'];
                }
                // Permitir otros métodos (máquina, tutor, huevo) indiscriminadamente?
                // Crear un set competitivo implica acceso a MTs.
                // Por ahora: Permitir todos los que no sean por nivel (asumiendo MTs/Tutor disponibles)
                elseif ($m['method'] !== 'level-up') {
                    $candidates[] = $m['name'];
                }
            }
        }
        else {
            // Fallback para datos antiguos o fallos
            $candidates = $pokemon['move_names'];
        }

        // Deduplicar
        $candidates = array_unique($candidates);

        $stabMoves = [];
        $coverageMoves = [];

        foreach ($candidates as $moveName) {
            $moveData = self::getOrFetchMove($moveName); // Esto obtiene stats (Tipo, Poder)

            if (!$moveData || ($moveData['power'] ?? 0) <= 0 && ($moveData['damage_class'] ?? 'physical') !== 'status')
                continue; // Saltar movimientos inútiles o puramente débiles? No, mantener de estado.

            // Cálculo de puntuación
            $score = $moveData['power'] ?? 0;

            // Aumentar prioridad de movimientos de Estado para no descartarlos siempre
            if (($moveData['damage_class'] ?? 'physical') === 'status') {
                $score = 40; // Equivalente a un ataque débil
            }

            $isStab = in_array($moveData['type'], $pokemonTypes);
            if ($isStab) {
                $score *= 1.5;
            }
            if (($moveData['accuracy'] ?? 100) >= 90) {
                $score *= 1.1;
            }

            // Penalizar movimientos con bajo PP
            if (($moveData['pp'] ?? 35) < 5) {
                $score *= 0.8;
            }

            $moveData['score'] = $score;
            $moveData['is_stab'] = $isStab;

            if ($isStab) {
                $stabMoves[] = $moveData;
            }
            else {
                $coverageMoves[] = $moveData;
            }
        }

        // Ordenar ambos por puntuación descendente
        usort($stabMoves, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        usort($coverageMoves, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        // Seleccionar: hasta 2 mejores STAB + rellenar con mejor cobertura
        $selected = [];
        $typesUsed = [];

        foreach ($stabMoves as $move) {
            if (count($selected) >= 2)
                break;
            $selected[] = $move;
            $typesUsed[$move['type']] = true;
        }

        foreach ($coverageMoves as $move) {
            if (count($selected) >= $count)
                break;
            // Preferir diversidad de tipos
            if (isset($typesUsed[$move['type']]) && count($coverageMoves) > ($count - count($selected)))
                continue;
            $selected[] = $move;
            $typesUsed[$move['type']] = true;
        }

        // Si falta cobertura, añadir cualquiera restante ignorando diversidad
        foreach ($coverageMoves as $move) {
            if (count($selected) >= $count)
                break;
            if (!in_array($move['name'], array_column($selected, 'name'))) {
                $selected[] = $move;
            }
        }

        // Si falta, añadir STAB restante
        foreach ($stabMoves as $move) {
            if (count($selected) >= $count)
                break;
            if (!in_array($move['name'], array_column($selected, 'name'))) {
                $selected[] = $move;
            }
        }

        // Rellenar restantes con defaults por tipo (sin llamadas API)
        if (count($selected) < $count) {
            $defaults = self::getDefaultMoves($pokemonTypes);
            foreach ($defaults as $defMove) {
                if (count($selected) >= $count)
                    break;
                if (!in_array($defMove['name'], array_column($selected, 'name'))) {
                    $selected[] = $defMove;
                }
            }
        }

        // Red de seguridad
        while (count($selected) < $count) {
            $selected[] = \App\Models\Move::where('name', 'tackle')->first()->toBattleArray() ?? MoveDatabase::getMove('tackle');
        }

        return array_map(function ($move) {
            return [
                'name' => $move['name'],
                'name_es' => $move['name_es'] ?? ucfirst(str_replace('-', ' ', $move['name'])),
                'power' => $move['power'] ?? 40,
                'accuracy' => $move['accuracy'] ?? 100,
                'pp' => $move['pp'] ?? 35,
                'current_pp' => $move['pp'] ?? 35,
                'type' => $move['type'] ?? 'normal',
                'damage_class' => $move['damage_class'] ?? 'physical',
                'status_effect' => $move['status_effect'] ?? null,
                'status_chance' => $move['status_chance'] ?? 0,
            ];
        }, array_values(array_slice($selected, 0, $count)));
    }

    private static function getDefaultMoves($pokemonTypes = ['normal'])
    {
        $primaryType = $pokemonTypes[0] ?? 'normal';

        // Movimientos de respaldo específicos por tipo
        $typeDefaults = [
            'fire' => ['name' => 'ember', 'name_es' => 'Ascuas', 'power' => 40, 'accuracy' => 100, 'pp' => 25, 'current_pp' => 25, 'type' => 'fire', 'damage_class' => 'special', 'status_effect' => 'burn', 'status_chance' => 10],
            'water' => ['name' => 'water-gun', 'name_es' => 'Pistola Agua', 'power' => 40, 'accuracy' => 100, 'pp' => 25, 'current_pp' => 25, 'type' => 'water', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'grass' => ['name' => 'vine-whip', 'name_es' => 'Látigo Cepa', 'power' => 45, 'accuracy' => 100, 'pp' => 25, 'current_pp' => 25, 'type' => 'grass', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'electric' => ['name' => 'thunder-shock', 'name_es' => 'Impactrueno', 'power' => 40, 'accuracy' => 100, 'pp' => 30, 'current_pp' => 30, 'type' => 'electric', 'damage_class' => 'special', 'status_effect' => 'paralysis', 'status_chance' => 10],
            'ice' => ['name' => 'ice-shard', 'name_es' => 'Canto Helado', 'power' => 40, 'accuracy' => 100, 'pp' => 30, 'current_pp' => 30, 'type' => 'ice', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'fighting' => ['name' => 'karate-chop', 'name_es' => 'Golpe Karate', 'power' => 50, 'accuracy' => 100, 'pp' => 25, 'current_pp' => 25, 'type' => 'fighting', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'poison' => ['name' => 'poison-sting', 'name_es' => 'Picotazo Ven', 'power' => 15, 'accuracy' => 100, 'pp' => 35, 'current_pp' => 35, 'type' => 'poison', 'damage_class' => 'physical', 'status_effect' => 'poison', 'status_chance' => 30],
            'ground' => ['name' => 'mud-slap', 'name_es' => 'Bofetón Lodo', 'power' => 20, 'accuracy' => 100, 'pp' => 10, 'current_pp' => 10, 'type' => 'ground', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'flying' => ['name' => 'gust', 'name_es' => 'Tornado', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'current_pp' => 35, 'type' => 'flying', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'psychic' => ['name' => 'confusion', 'name_es' => 'Confusión', 'power' => 50, 'accuracy' => 100, 'pp' => 25, 'current_pp' => 25, 'type' => 'psychic', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'bug' => ['name' => 'bug-bite', 'name_es' => 'Picadura', 'power' => 60, 'accuracy' => 100, 'pp' => 20, 'current_pp' => 20, 'type' => 'bug', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'rock' => ['name' => 'rock-throw', 'name_es' => 'Lanzarrocas', 'power' => 50, 'accuracy' => 90, 'pp' => 15, 'current_pp' => 15, 'type' => 'rock', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'ghost' => ['name' => 'shadow-ball', 'name_es' => 'Bola Sombra', 'power' => 80, 'accuracy' => 100, 'pp' => 15, 'current_pp' => 15, 'type' => 'ghost', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'dragon' => ['name' => 'dragon-rage', 'name_es' => 'Furia Dragón', 'power' => 40, 'accuracy' => 100, 'pp' => 10, 'current_pp' => 10, 'type' => 'dragon', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'dark' => ['name' => 'bite', 'name_es' => 'Mordisco', 'power' => 60, 'accuracy' => 100, 'pp' => 25, 'current_pp' => 25, 'type' => 'dark', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'steel' => ['name' => 'metal-claw', 'name_es' => 'Garra Metal', 'power' => 50, 'accuracy' => 95, 'pp' => 35, 'current_pp' => 35, 'type' => 'steel', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            'fairy' => ['name' => 'disarming-voice', 'name_es' => 'Voz Cautivad.', 'power' => 40, 'accuracy' => 100, 'pp' => 15, 'current_pp' => 15, 'type' => 'fairy', 'damage_class' => 'special', 'status_effect' => null, 'status_chance' => 0],
            'normal' => ['name' => 'tackle', 'name_es' => 'Placaje', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'current_pp' => 35, 'type' => 'normal', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
        ];

        $defaultMoves = [];
        // Añadir movimiento STAB para el tipo primario
        $defaultMoves[] = $typeDefaults[$primaryType] ?? $typeDefaults['normal'];
        // Añadir segundo STAB si es dual-type
        if (isset($pokemonTypes[1]) && isset($typeDefaults[$pokemonTypes[1]])) {
            $defaultMoves[] = $typeDefaults[$pokemonTypes[1]];
        }
        // Rellenar resto con normales genéricos
        $normalFillers = [
            ['name' => 'tackle', 'name_es' => 'Placaje', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'current_pp' => 35, 'type' => 'normal', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            ['name' => 'scratch', 'name_es' => 'Arañazo', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'current_pp' => 35, 'type' => 'normal', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            ['name' => 'quick-attack', 'name_es' => 'Ataque Rápido', 'power' => 40, 'accuracy' => 100, 'pp' => 30, 'current_pp' => 30, 'type' => 'normal', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
            ['name' => 'pound', 'name_es' => 'Destructor', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'current_pp' => 35, 'type' => 'normal', 'damage_class' => 'physical', 'status_effect' => null, 'status_chance' => 0],
        ];
        foreach ($normalFillers as $filler) {
            if (count($defaultMoves) >= 4)
                break;
            if (!in_array($filler['name'], array_column($defaultMoves, 'name'))) {
                $defaultMoves[] = $filler;
            }
        }
        return $defaultMoves;
    }

    public static function getPokemonList($page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $cacheKey = "pokemon_list_{$page}_{$limit}_v3"; // Version bump

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // ¿Intentar BD primero?
        // Sincronizar la lista es complicado si no tenemos todos.
        // Por ahora, mantener getPokemonList vía API pero GUARDAR los pokemons individuales encontrados.
        // En realidad, el usuario pidió "actualizar datos para que el caché no se llene y cargue más rápido".
        // Una lista completa de BD sería más rápida.

        $client = new Client();
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon?offset={$offset}&limit={$limit}");
            $data = json_decode($response->getBody()->getContents(), true);
            $pokemons = [];
            foreach ($data['results'] as $pokemon) {
                $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                $id = end($urlParts);

                // Optimizado: obtener detalle dispara guardado en BD
                $details = self::getPokemon($id);

                $pokemons[] = [
                    'id' => $id,
                    'name' => ucfirst($pokemon['name']),
                    'image' => "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/{$id}.png",
                    'types' => $details['types'] ?? ['normal']
                ];
            }
            $result = [
                'pokemons' => $pokemons,
                'total' => $data['count'],
                'next' => $data['next'],
                'previous' => $data['previous'],
                'current_page' => floor($offset / $limit) + 1,
                'total_pages' => ceil($data['count'] / $limit)
            ];

            Cache::put($cacheKey, $result, 3600);
            return $result;
        }
        catch (\Exception $e) {
            return ['pokemons' => [], 'total' => 0, 'next' => null, 'previous' => null, 'current_page' => 1, 'total_pages' => 1];
        }
    }

    public static function searchPokemon($query)
    {
        $cacheKey = "pokemon_search_results_" . md5(strtolower($query)) . "_v2";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $query = strtolower(trim($query));
        $results = [];

        if (is_numeric($query)) {
            $pokemon = self::getPokemon($query);
            if ($pokemon) {
                $results[] = [
                    'id' => $pokemon['id'],
                    'name' => $pokemon['name'],
                    'image' => "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/{$pokemon['id']}.png",
                    'types' => $pokemon['types']
                ];
                Cache::put($cacheKey, $results, 3600);
                return $results;
            }
        }

        $allPokemon = self::getAllPokemonNames();
        foreach ($allPokemon as $id => $name) {
            if (str_contains(strtolower($name), $query)) {
                $details = self::getPokemon($id);
                $results[] = [
                    'id' => $id,
                    'name' => ucfirst($name),
                    'image' => "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/{$id}.png",
                    'types' => $details['types'] ?? ['normal']
                ];
                if (count($results) >= 50)
                    break;
            }
        }

        if (!empty($results)) {
            Cache::put($cacheKey, $results, 3600);
        }
        return $results;
    }

    private static function getAllPokemonNames()
    {
        $cacheKey = 'all_pokemon_names_list';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client();
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon?limit=2000");
            $data = json_decode($response->getBody()->getContents(), true);
            $list = [];
            foreach ($data['results'] as $p) {
                $parts = explode('/', rtrim($p['url'], '/'));
                $id = end($parts);
                $list[$id] = $p['name'];
            }

            if (!empty($list)) {
                Cache::put($cacheKey, $list, 86400);
            }
            return $list;
        }
        catch (\Exception $e) {
            return [];
        }
    }

    public static function getSimplePokemonList($limit = Pokemon::MAX_ID)
    {
        $cacheKey = "simple_pokemon_list_{$limit}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client();
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon?limit={$limit}");
            $data = json_decode($response->getBody()->getContents(), true);
            $list = [];
            foreach ($data['results'] as $p) {
                $parts = explode('/', rtrim($p['url'], '/'));
                $id = end($parts);
                $list[$id] = ucfirst($p['name']);
            }

            if (!empty($list)) {
                Cache::put($cacheKey, $list, 86400);
            }
            return $list;
        }
        catch (\Exception $e) {
            return [];
        }
    }

    public static function getTypeColor($type)
    {
        $colors = config('pokemon.type_colors', []);

        return $colors[strtolower($type)] ?? $colors['default'] ?? '#777777';
    }

    public static function getOrFetchMove($moveName)
    {
        // 1. Verificar en Base de Datos MySQL
        try {
            $moveModel = \App\Models\Move::where('name', $moveName)->first();

            // Verificar si el modelo existe Y tiene suficientes datos.
            // Usamos 'name_es' como bandera porque se llena desde la API pero es null por defecto en migración.
            // Si name_es está presente, asumimos que los detalles del movimiento fueron obtenidos.
            if ($moveModel && !empty($moveModel->name_es)) {
                return $moveModel->toBattleArray();
            }
        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("PokemonHelper::getOrFetchMove($moveName) DB lookup failed: " . $e->getMessage());
        }

        // 2. Obtener de PokéAPI
        $apiData = self::getMoveDetails($moveName);
        if ($apiData) {
            // 3. Guardar en MySQL (Actualizar si existe, Crear si no)
            try {
                \App\Models\Move::updateOrCreate(
                ['name' => $moveName],
                [
                    'name_es' => $apiData['name_es'] ?? ucfirst(str_replace('-', ' ', $moveName)),
                    'power' => $apiData['power'],
                    'accuracy' => $apiData['accuracy'],
                    'pp' => $apiData['pp'],
                    'type' => $apiData['type'],
                    'damage_class' => $apiData['damage_class'],
                    'status_effect' => $apiData['status_effect'],
                    'status_chance' => $apiData['status_chance'],
                    'priority' => $apiData['priority'],
                ]
                );
            }
            catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("PokemonHelper::getOrFetchMove($moveName) failed to save to DB: " . $e->getMessage());
            }
            return $apiData;
        }

        // 4. Fallback de Emergencia
        if ($moveModel) {
            // Incluso si está incompleto, mejor que nada si la API falló
            return $moveModel->toBattleArray();
        }

        return MoveDatabase::getMove($moveName);
    }
}