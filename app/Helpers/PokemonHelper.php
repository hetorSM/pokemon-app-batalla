<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Repositories\PokemonRepository;
use App\Services\PokeApiService;
use App\Services\BattleCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use App\Models\Pokemon;

class PokemonHelper
{
    // Facade methods to maintain static access for controllers

    public static function getPokemon(int|string $id): ?array
    {
        $cacheKey = "pokemon_{$id}_full_v2";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        /** @var PokemonRepository $repo */
        $repo = App::make(PokemonRepository::class);
        /** @var PokeApiService $api */
        $api = App::make(PokeApiService::class);

        // 1. Check DB
        $pokemonModel = $repo->findByApiId($id);

        if ($pokemonModel) {
            $data = self::formatPokemonModel($pokemonModel);
            Cache::put($cacheKey, $data, 3600);
            return $data;
        }

        // 2. Fetch from API
        $data = $api->fetchPokemon($id);

        if (!$data) {
            return null;
        }

        // 3. Save to DB
        $pokemonModel = $repo->savePokemonFromApiData($data);

        // 4. Format and Return
        $pokemonModel->load(['types', 'stats', 'moves', 'sprite', 'cry']);
        $formatted = self::formatPokemonModel($pokemonModel);

        // Evolutions (still fetched via API for now, could be its own service method)
        $formatted['evolutions'] = self::fetchEvolutions($id, $api);

        Cache::put($cacheKey, $formatted, 3600);
        return $formatted;
    }

    private static function formatPokemonModel($pokemon)
    {
        return [
            'id' => $pokemon->api_id,
            'name' => ucfirst($pokemon->name),
            'image' => $pokemon->sprite->official_artwork ?? $pokemon->sprite->front_default ?? "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{$pokemon->api_id}.png",
            'types' => $pokemon->types->pluck('name')->toArray(),
            'stats' => $pokemon->stats->mapWithKeys(fn($s) => [$s->name => $s->pivot->base_value])->toArray(),
            'move_names' => $pokemon->moves->pluck('name')->toArray(),
            'moves_detailed' => $pokemon->moves->map(fn($m) => [
        'name' => $m->name,
        'level' => $m->pivot->level_learned_at,
        'method' => $m->pivot->learn_method
        ])->sortBy('level')->values()->toArray(),
            'cries' => $pokemon->cry ? ['latest' => $pokemon->cry->latest, 'legacy' => $pokemon->cry->legacy] : [],
            'evolutions' => [],
            'base_experience' => $pokemon->base_experience,
            'height' => $pokemon->height,
            'weight' => $pokemon->weight,
        ];
    }

    private static function fetchEvolutions($id, PokeApiService $api)
    {
        try {
            $apiData = $api->fetchPokemon($id);
            if (!$apiData)
                return [];

            $speciesData = $api->fetchUrl($apiData['species']['url']);
            if (!$speciesData)
                return [];

            $evoData = $api->fetchUrl($speciesData['evolution_chain']['url']);
            if (!$evoData)
                return [];

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

    public static function getMoveDetails(string $moveName): ?array
    {
        $cacheKey = "move_{$moveName}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        /** @var PokeApiService $api */
        $api = App::make(PokeApiService::class);
        $data = $api->fetchMove($moveName);

        if (!$data)
            return null;

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

    public static function selectBattleMoves($pokemonId, $count = 4, $level = 50)
    {
        /** @var BattleCalculator $calc */
        $calc = App::make(BattleCalculator::class);

        $pokemon = self::getPokemon($pokemonId);
        if (!$pokemon || empty($pokemon['move_names'])) {
            return $calc->getDefaultMoves($pokemon['types'] ?? ['normal']);
        }

        // Logic from Helper now moved to Calculator?
        // Actually, to avoid breaking logic, we need to replicate the selection strategy.
        // The Calculator::selectSmartMoves was a placeholder. 
        // Let's implement the FULL original logic here but using the calculator for scoring.

        $pokemonTypes = $pokemon['types'] ?? ['normal'];
        $candidates = [];

        if (!empty($pokemon['moves_detailed'])) {
            foreach ($pokemon['moves_detailed'] as $m) {
                if ($m['method'] === 'level-up' && $m['level'] <= $level) {
                    $candidates[] = $m['name'];
                }
                elseif ($m['method'] !== 'level-up') {
                    $candidates[] = $m['name'];
                }
            }
        }
        else {
            $candidates = $pokemon['move_names'];
        }

        $candidates = array_unique($candidates);
        $stabMoves = [];
        $coverageMoves = [];

        foreach ($candidates as $moveName) {
            $moveData = self::getOrFetchMove($moveName);

            if (!$moveData || ($moveData['power'] ?? 0) <= 0 && ($moveData['damage_class'] ?? 'physical') !== 'status')
                continue;

            // Use Calculator for scoring
            $score = $calc->calculateMoveScore($moveData, $pokemonTypes);

            $moveData['score'] = $score;
            $moveData['is_stab'] = in_array($moveData['type'], $pokemonTypes);

            if ($moveData['is_stab']) {
                $stabMoves[] = $moveData;
            }
            else {
                $coverageMoves[] = $moveData;
            }
        }

        usort($stabMoves, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        usort($coverageMoves, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

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
            if (isset($typesUsed[$move['type']]) && count($coverageMoves) > ($count - count($selected)))
                continue;
            $selected[] = $move;
            $typesUsed[$move['type']] = true;
        }

        foreach ($coverageMoves as $move) {
            if (count($selected) >= $count)
                break;
            if (!in_array($move['name'], array_column($selected, 'name'))) {
                $selected[] = $move;
            }
        }

        foreach ($stabMoves as $move) {
            if (count($selected) >= $count)
                break;
            if (!in_array($move['name'], array_column($selected, 'name'))) {
                $selected[] = $move;
            }
        }

        if (count($selected) < $count) {
            $defaults = $calc->getDefaultMoves($pokemonTypes);
            foreach ($defaults as $defMove) {
                if (count($selected) >= $count)
                    break;
                if (!in_array($defMove['name'], array_column($selected, 'name'))) {
                    $selected[] = $defMove;
                }
            }
        }

        while (count($selected) < $count) {
            $selected[] = MoveDatabase::getMove('tackle'); // Fallback
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

    public static function getPokemonList($page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $cacheKey = "pokemon_list_{$page}_{$limit}_v3";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        /** @var PokeApiService $api */
        $api = App::make(PokeApiService::class);
        $data = $api->fetchPokemonList($limit, $offset);

        if (!$data)
            return ['pokemons' => [], 'total' => 0, 'next' => null, 'previous' => null, 'current_page' => 1, 'total_pages' => 1];

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

    public static function getSimplePokemonList($limit = Pokemon::MAX_ID)
    {
        // Re-use logic or ApiService? ApiService fetchPokemonList is paginated.
        // Let's explicitly fetch large list.
        $cacheKey = "simple_pokemon_list_{$limit}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        /** @var PokeApiService $api */
        $api = App::make(PokeApiService::class);
        $data = $api->fetchPokemonList($limit, 0);

        if (!$data)
            return [];

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

    public static function getTypeColor($type)
    {
        $colors = config('pokemon.type_colors', []);
        return $colors[strtolower($type)] ?? $colors['default'] ?? '#777777';
    }

    public static function getOrFetchMove($moveName)
    {
        /** @var PokemonRepository $repo */
        $repo = App::make(PokemonRepository::class);

        $moveModel = $repo->findMoveByName($moveName);

        if ($moveModel && !empty($moveModel->name_es)) {
            return $moveModel->toBattleArray();
        }

        // Fetch from API
        $apiData = self::getMoveDetails($moveName);
        if ($apiData) {
            $repo->saveMoveFromApiData($apiData);
            return $apiData;
        }

        if ($moveModel) {
            return $moveModel->toBattleArray();
        }

        return MoveDatabase::getMove($moveName);
    }

    // Missing searchPokemon and getAllPokemonNames re-implementation if needed, 
    // but they are just variations of getPokemonList/getPokemon. 
    // I will include them to keep full compatibility.

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

        /** @var PokeApiService $api */
        $api = App::make(PokeApiService::class);
        $data = $api->fetchPokemonList(2000, 0);

        if (!$data)
            return [];

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
}