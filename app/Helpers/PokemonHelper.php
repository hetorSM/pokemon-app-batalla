<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class PokemonHelper
{
    public static function getPokemon($id)
    {
        // Cache por 1 hora para no saturar la API (no cachea errores)
        $cacheKey = "pokemon_{$id}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client();

        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon/{$id}");
            $data = json_decode($response->getBody(), true);

            // Obtener datos de la especie para la cadena de evolución
            $speciesResponse = $client->get($data['species']['url']);
            $speciesData = json_decode($speciesResponse->getBody(), true);

            $evoResponse = $client->get($speciesData['evolution_chain']['url']);
            $evoData = json_decode($evoResponse->getBody(), true);

            $evolutions = self::parseEvolutionChain($evoData['chain']);

            $result = [
                'id' => $data['id'],
                'name' => ucfirst($data['name']),
                'image' => $data['sprites']['other']['official-artwork']['front_default']
                ?? $data['sprites']['front_default']
                ?? "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$id}.png",
                'types' => array_map(function ($type) {
                return $type['type']['name'];
            }, $data['types']),
                'stats' => [
                    'hp' => $data['stats'][0]['base_stat'],
                    'attack' => $data['stats'][1]['base_stat'],
                    'defense' => $data['stats'][2]['base_stat'],
                    'special-attack' => $data['stats'][3]['base_stat'],
                    'special-defense' => $data['stats'][4]['base_stat'],
                    'speed' => $data['stats'][5]['base_stat'],
                ],
                'move_names' => array_map(function ($m) {
                return $m['move']['name'];
            }, $data['moves']),
                'cries' => $data['cries'] ?? null,
                'evolutions' => $evolutions,
            ];

            Cache::put($cacheKey, $result, 3600);
            return $result;
        }
        catch (\Exception $e) {
            return null;
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

    public static function selectBattleMoves($pokemonId, $count = 4)
    {
        $pokemon = self::getPokemon($pokemonId);
        if (!$pokemon || empty($pokemon['move_names'])) {
            return self::getDefaultMoves($pokemon['types'] ?? ['normal']);
        }

        $pokemonTypes = $pokemon['types'] ?? ['normal'];
        $allMoveNames = $pokemon['move_names'];

        $stabMoves = [];
        $coverageMoves = [];

        foreach ($allMoveNames as $moveName) {
            // Updated logic: Try DB -> API -> Fallback
            $moveData = self::getOrFetchMove($moveName);

            // If still null (API failed and not in emergency DB), skip
            if (!$moveData)
                continue;

            $score = $moveData['power'];
            $isStab = in_array($moveData['type'], $pokemonTypes);
            if ($isStab) {
                $score *= 1.5;
            }
            if (($moveData['accuracy'] ?? 100) >= 90) {
                $score *= 1.1;
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
            $selected[] = MoveDatabase::getMove('tackle');
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
        $cacheKey = "pokemon_list_{$page}_{$limit}_v2";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client();
        try {
            $response = $client->get("https://pokeapi.co/api/v2/pokemon?offset={$offset}&limit={$limit}");
            $data = json_decode($response->getBody(), true);
            $pokemons = [];
            foreach ($data['results'] as $pokemon) {
                $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                $id = end($urlParts);
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

    private static function getOrFetchMove($moveName)
    {
        // 1. Check in MySQL Database
        try {
            $moveModel = \App\Models\Move::where('name', $moveName)->first();
            if ($moveModel) {
                return $moveModel->toBattleArray();
            }
        }
        catch (\Exception $e) {
        // DB error, proceed to fetch
        }

        // 2. Fetch from PokéAPI
        $apiData = self::getMoveDetails($moveName);
        if ($apiData) {
            // 3. Save to MySQL
            try {
                \App\Models\Move::create([
                    'name' => $apiData['name'],
                    'name_es' => $apiData['name_es'],
                    'power' => $apiData['power'],
                    'accuracy' => $apiData['accuracy'],
                    'pp' => $apiData['pp'],
                    'type' => $apiData['type'],
                    'damage_class' => $apiData['damage_class'],
                    'status_effect' => $apiData['status_effect'],
                    'status_chance' => $apiData['status_chance'],
                    'priority' => $apiData['priority'],
                ]);
            }
            catch (\Exception $e) {
            // Ignore DB errors, just return data
            }
            return $apiData;
        }

        // 4. Emergency Fallback
        return MoveDatabase::getMove($moveName);
    }
}