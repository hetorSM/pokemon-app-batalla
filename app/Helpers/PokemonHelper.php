<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class PokemonHelper
{
    public static function getPokemon($id)
    {
        // 0. Check internal memory/cache first (Laravel Cache)
        $cacheKey = "pokemon_{$id}_full_v2";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 1. Check DB Persistence (Normalized)
        $pokemonModel = \App\Models\Pokemon::with(['types', 'stats', 'moves', 'sprite', 'cry'])
            ->where('api_id', $id)
            ->first();

        if ($pokemonModel) {
            $data = self::formatPokemonModel($pokemonModel);
            Cache::put($cacheKey, $data, 3600);
            return $data;
        }

        // 2. Fetch from API if not in DB
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon/{$id}");
            $data = json_decode($response->getBody(), true);

            // DB Transaction to ensure data integrity
            $pokemonModel = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $id) {
                // A. Create/Update Pokemon Base
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

                // E. Cries (if available in API response, otherwise skipped)
                if (isset($data['cries'])) {
                    \App\Models\PokemonCry::updateOrCreate(
                    ['pokemon_id' => $pokemon->id],
                    [
                        'latest' => $data['cries']['latest'] ?? null,
                        'legacy' => $data['cries']['legacy'] ?? null,
                    ]
                    );
                }

                // F. Moves (Heavy operation)
                // Filter for a specific version group to avoid clutter. 
                // Let's use 'scarlet-violet' -> 'sword-shield' -> 'ultra-sun-ultra-moon' -> generic
                // Actually, storing ALL valid moves is better but duplicate names exist.
                // We will iterate and prioritize the LATEST learn method/level for each move.

                $movesToAttach = [];
                foreach ($data['moves'] as $m) {
                    $moveName = $m['move']['name'];

                    // Allow simple move creation if it doesn't exist (details fetched later)
                    $moveDB = \App\Models\Move::firstOrCreate(['name' => $moveName]);

                    // Find level-up data
                    $level = 0;
                    $method = 'machine'; // Default fallback

                    // Search for level-up in latest versions
                    foreach ($m['version_group_details'] as $vgd) {
                        if ($vgd['move_learn_method']['name'] === 'level-up') {
                            $level = $vgd['level_learned_at'];
                            $method = 'level-up';
                            break; // Take the first level-up found (usually earliest/latest depending on sort, API order varies)
                        }
                    }

                    // If not level-up, take the first available method data
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

            // Reload to get relations
            $pokemonModel->load(['types', 'stats', 'moves', 'sprite', 'cry']);
            $formatted = self::formatPokemonModel($pokemonModel);

            // Add Evolutions (This part remains API-dependent for now to save time, or we can persist species too)
            // For now, fetch lively as before to avoid complex species tables
            $formatted['evolutions'] = self::fetchEvolutions($id, $client);

            Cache::put($cacheKey, $formatted, 3600);
            return $formatted;

        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error fetching Pokemon {$id}: " . $e->getMessage());
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
            'move_names' => $pokemon->moves->pluck('name')->toArray(), // Backward compatibility
            'moves_detailed' => $pokemon->moves->map(fn($m) => [
            'name' => $m->name,
            'level' => $m->pivot->level_learned_at,
            'method' => $m->pivot->learn_method
            ])->sortBy('level')->values()->toArray(),
            'cries' => $pokemon->cry ? ['latest' => $pokemon->cry->latest, 'legacy' => $pokemon->cry->legacy] : [],
            'evolutions' => [], // Filled separately if needed or cached
            'base_experience' => $pokemon->base_experience,
            'height' => $pokemon->height,
            'weight' => $pokemon->weight,
        ];
    }

    private static function fetchEvolutions($id, $client)
    {
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon/{$id}");
            $apiData = json_decode($response->getBody(), true);
            $speciesResponse = $client->get($apiData['species']['url']);
            $speciesData = json_decode($speciesResponse->getBody(), true);
            $evoResponse = $client->get($speciesData['evolution_chain']['url']);
            $evoData = json_decode($evoResponse->getBody(), true);
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
            $data = json_decode($response->getBody(), true);

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

        // Use detailed moves if available (from new logic)
        $candidates = [];
        if (!empty($pokemon['moves_detailed'])) {
            foreach ($pokemon['moves_detailed'] as $m) {
                // Logic: Allow moves learned by level up <= current level
                if ($m['method'] === 'level-up' && $m['level'] <= $level) {
                    $candidates[] = $m['name'];
                }
                // Allow other methods (machine, tutor, egg) indiscriminately? 
                // Creating a competitive set usually implies access to TMs. 
                // Let's allow them but maybe deprioritize if we want "natural" feel.
                // For now: Allow all non-level-up moves (assumed TMs/Tutors available)
                elseif ($m['method'] !== 'level-up') {
                    $candidates[] = $m['name'];
                }
            }
        }
        else {
            // Fallback for old data or failures
            $candidates = $pokemon['move_names'];
        }

        // Deduplicate
        $candidates = array_unique($candidates);

        $stabMoves = [];
        $coverageMoves = [];

        foreach ($candidates as $moveName) {
            $moveData = self::getOrFetchMove($moveName); // This fetches stats (Type, Power)

            if (!$moveData || ($moveData['power'] ?? 0) <= 0 && ($moveData['damage_class'] ?? 'physical') !== 'status')
                continue; // Skip useless moves or purely weak ones? No, keep status moves.

            // Score calculation
            $score = $moveData['power'] ?? 0;

            // Boost Status moves priority slightly so they aren't always discarded if power is 0
            if (($moveData['damage_class'] ?? 'physical') === 'status') {
                $score = 40; // Equivalent to a weak attack
            }

            $isStab = in_array($moveData['type'], $pokemonTypes);
            if ($isStab) {
                $score *= 1.5;
            }
            if (($moveData['accuracy'] ?? 100) >= 90) {
                $score *= 1.1;
            }

            // Penalize moves with low PP
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

        // Sort both by score descending
        usort($stabMoves, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        usort($coverageMoves, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        // Select: up to 2 best STAB + fill with best coverage
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
            // Prefer type diversity
            if (isset($typesUsed[$move['type']]) && count($coverageMoves) > ($count - count($selected)))
                continue;
            $selected[] = $move;
            $typesUsed[$move['type']] = true;
        }

        // If still lacking coverage, add any remaining coverage ignoring diversity
        foreach ($coverageMoves as $move) {
            if (count($selected) >= $count)
                break;
            if (!in_array($move['name'], array_column($selected, 'name'))) {
                $selected[] = $move;
            }
        }

        // If still lacking, add remaining STAB
        foreach ($stabMoves as $move) {
            if (count($selected) >= $count)
                break;
            if (!in_array($move['name'], array_column($selected, 'name'))) {
                $selected[] = $move;
            }
        }

        // Fill remaining with type defaults (no API calls)
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

        // Safety net
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

        // Type-specific fallback moves
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
        // Add a STAB move for the primary type
        $defaultMoves[] = $typeDefaults[$primaryType] ?? $typeDefaults['normal'];
        // Add a second STAB if dual-type
        if (isset($pokemonTypes[1]) && isset($typeDefaults[$pokemonTypes[1]])) {
            $defaultMoves[] = $typeDefaults[$pokemonTypes[1]];
        }
        // Fill rest with generic Normal moves
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

        // Try DB first? 
        // Syncing the list is tricky if we don't have all of them. 
        // For now, let's keep getPokemonList via API but SAVE individual pokemons we find.
        // Actually, the user asked to "update data so cache is not full and load faster".
        // A full DB list would be faster.

        $client = new Client();
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon?offset={$offset}&limit={$limit}");
            $data = json_decode($response->getBody(), true);
            $pokemons = [];
            foreach ($data['results'] as $pokemon) {
                $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                $id = end($urlParts);

                // Optimized: get detail triggers save to DB
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
            $data = json_decode($response->getBody(), true);
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

    public static function getSimplePokemonList($limit = 1025)
    {
        $cacheKey = "simple_pokemon_list_{$limit}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client();
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon?limit={$limit}");
            $data = json_decode($response->getBody(), true);
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
        $colors = [
            'normal' => '#A8A878',
            'fire' => '#F08030',
            'water' => '#6890F0',
            'grass' => '#78C850',
            'electric' => '#F8D030',
            'ice' => '#98D8D8',
            'fighting' => '#C03028',
            'poison' => '#A040A0',
            'ground' => '#E0C068',
            'flying' => '#A890F0',
            'psychic' => '#F85888',
            'bug' => '#A8B820',
            'rock' => '#B8A038',
            'ghost' => '#705898',
            'dragon' => '#7038F8',
            'dark' => '#705848',
            'steel' => '#B8B8D0',
            'fairy' => '#EE99AC',
        ];

        return $colors[strtolower($type)] ?? '#777777';
    }

    public static function getOrFetchMove($moveName)
    {
        // 1. Check in MySQL Database
        try {
            $moveModel = \App\Models\Move::where('name', $moveName)->first();

            // Check if model exists AND has sufficient data.
            // We use 'name_es' as a flag because it's populated from API but null by default in migration.
            // If name_es is present, we assume the move details were fetched.
            if ($moveModel && !empty($moveModel->name_es)) {
                return $moveModel->toBattleArray();
            }
        }
        catch (\Exception $e) {
        // DB error, proceed to fetch
        }

        // 2. Fetch from PokéAPI
        $apiData = self::getMoveDetails($moveName);
        if ($apiData) {
            // 3. Save to MySQL (Update if exists, Create if not)
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
            // Ignore DB errors, just return data
            }
            return $apiData;
        }

        // 4. Emergency Fallback
        if ($moveModel) {
            // Even if incomplete, better than nothing if API failed
            return $moveModel->toBattleArray();
        }

        return MoveDatabase::getMove($moveName);
    }
}