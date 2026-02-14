<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\MoveDatabase;

class BattleCalculator
{


    // Since selectBattleMoves deeply integrates fetching and logic, 
    // it might be better to keep the orchestration in PokemonHelper for now
    // and move the *scoring* logic here.

    public function calculateMoveScore(array $moveData, array $pokemonTypes): float
    {
        $score = (float)($moveData['power'] ?? 0);

        // Status moves priority
        if (($moveData['damage_class'] ?? 'physical') === 'status') {
            $score = 40;
        }

        // STAB
        $isStab = in_array($moveData['type'], $pokemonTypes);
        if ($isStab) {
            $score *= 1.5;
        }

        // Accuracy
        if (($moveData['accuracy'] ?? 100) >= 90) {
            $score *= 1.1;
        }

        // PP penalty
        if (($moveData['pp'] ?? 35) < 5) {
            $score *= 0.8;
        }

        return $score;
    }

    public function getDefaultMoves(array $pokemonTypes = ['normal']): array
    {
        $primaryType = $pokemonTypes[0] ?? 'normal';
        $typeDefaults = config('pokemon.type_defaults', []); // Assuming we might move this to config

        // Hardcoded fallback if config is missing (migrated from Helper)
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
        $defaultMoves[] = $typeDefaults[$primaryType] ?? $typeDefaults['normal'];
        if (isset($pokemonTypes[1]) && isset($typeDefaults[$pokemonTypes[1]])) {
            $defaultMoves[] = $typeDefaults[$pokemonTypes[1]];
        }

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
}