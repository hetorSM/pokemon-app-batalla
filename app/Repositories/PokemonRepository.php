<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pokemon;
use App\Models\Move;
use App\Models\Type;
use App\Models\Stat;
use App\Models\PokemonSprite;
use App\Models\PokemonCry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PokemonRepository
{
    public function findByApiId(int|string $id): ?Pokemon
    {
        return Pokemon::with(['types', 'stats', 'moves', 'sprite', 'cry'])
            ->where('api_id', $id)
            ->first();
    }

    public function findMoveByName(string $name): ?Move
    {
        return Move::where('name', $name)->first();
    }

    public function findMovesByNames(array $names)
    {
        return Move::whereIn('name', $names)->get()->keyBy('name');
    }

    public function savePokemonFromApiData(array $data): Pokemon
    {
        return DB::transaction(function () use ($data) {
            // A. Create/Update Base Pokemon
            $pokemon = Pokemon::updateOrCreate(
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
                $type = Type::firstOrCreate(['name' => $t['type']['name']]);
                $pokemon->types()->attach($type->id, ['slot' => $t['slot']]);
            }

            // C. Stats
            $pokemon->stats()->detach();
            foreach ($data['stats'] as $s) {
                $stat = Stat::firstOrCreate(['name' => $s['stat']['name']]);
                $pokemon->stats()->attach($stat->id, ['base_value' => $s['base_stat']]);
            }

            // D. Sprites
            PokemonSprite::updateOrCreate(
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
                PokemonCry::updateOrCreate(
                ['pokemon_id' => $pokemon->id],
                [
                    'latest' => $data['cries']['latest'] ?? null,
                    'legacy' => $data['cries']['legacy'] ?? null,
                ]
                );
            }

            // F. Moves (Optimized Bulk Operation)
            $this->syncMoves($pokemon, $data['moves']);

            return $pokemon;
        });
    }

    private function syncMoves(Pokemon $pokemon, array $apiMoves): void
    {
        $movesToAttach = [];
        $moveNames = array_map(fn($m) => $m['move']['name'], $apiMoves);

        // Fetch existing moves
        $existingMoves = $this->findMovesByNames($moveNames);

        // Identify missing moves
        $missingMoves = [];
        foreach ($moveNames as $name) {
            if (!$existingMoves->has($name)) {
                $missingMoves[] = ['name' => $name, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        // Insert missing moves in bulk
        if (!empty($missingMoves)) {
            Move::insert($missingMoves);
            $existingMoves = $this->findMovesByNames($moveNames);
        }

        foreach ($apiMoves as $m) {
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
    }

    public function saveMoveFromApiData(array $apiData): Move
    {
        return Move::updateOrCreate(
        ['name' => $apiData['name']],
        [
            'name_es' => $apiData['name_es'] ?? ucfirst(str_replace('-', ' ', $apiData['name'])),
            'power' => $apiData['power'],
            'accuracy' => $apiData['accuracy'],
            'pp' => $apiData['pp'],
            'type' => $apiData['type'] ?? $apiData['type']['name'] ?? 'normal', // Handle API structure variation
            'damage_class' => $apiData['damage_class'] ?? $apiData['damage_class']['name'] ?? 'physical',
            'status_effect' => $apiData['status_effect'],
            'status_chance' => $apiData['status_chance'],
            'priority' => $apiData['priority'] ?? 0,
        ]
        );
    }
}