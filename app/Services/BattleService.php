<?php

namespace App\Services;

use App\Helpers\PokemonHelper;

class BattleService
{
    // Tabla de efectividades completa (18 tipos)
    private $typeChart = [
        'normal' => ['rock' => 0.5, 'ghost' => 0, 'steel' => 0.5],
        'fire' => ['fire' => 0.5, 'water' => 0.5, 'grass' => 2, 'ice' => 2, 'bug' => 2, 'rock' => 0.5, 'dragon' => 0.5, 'steel' => 2],
        'water' => ['fire' => 2, 'water' => 0.5, 'grass' => 0.5, 'ground' => 2, 'rock' => 2, 'dragon' => 0.5],
        'grass' => ['fire' => 0.5, 'water' => 2, 'grass' => 0.5, 'poison' => 0.5, 'ground' => 2, 'flying' => 0.5, 'bug' => 0.5, 'rock' => 2, 'dragon' => 0.5, 'steel' => 0.5],
        'electric' => ['water' => 2, 'electric' => 0.5, 'grass' => 0.5, 'ground' => 0, 'flying' => 2, 'dragon' => 0.5],
        'ice' => ['fire' => 0.5, 'water' => 0.5, 'grass' => 2, 'ice' => 0.5, 'ground' => 2, 'flying' => 2, 'dragon' => 2, 'steel' => 0.5],
        'fighting' => ['normal' => 2, 'ice' => 2, 'poison' => 0.5, 'flying' => 0.5, 'psychic' => 0.5, 'bug' => 0.5, 'rock' => 2, 'ghost' => 0, 'dark' => 2, 'steel' => 2, 'fairy' => 0.5],
        'poison' => ['grass' => 2, 'poison' => 0.5, 'ground' => 0.5, 'rock' => 0.5, 'ghost' => 0.5, 'steel' => 0, 'fairy' => 2],
        'ground' => ['fire' => 2, 'electric' => 2, 'grass' => 0.5, 'poison' => 2, 'flying' => 0, 'bug' => 0.5, 'rock' => 2, 'steel' => 2],
        'flying' => ['electric' => 0.5, 'grass' => 2, 'fighting' => 2, 'bug' => 2, 'rock' => 0.5, 'steel' => 0.5],
        'psychic' => ['fighting' => 2, 'poison' => 2, 'psychic' => 0.5, 'dark' => 0, 'steel' => 0.5],
        'bug' => ['fire' => 0.5, 'grass' => 2, 'fighting' => 0.5, 'poison' => 0.5, 'flying' => 0.5, 'psychic' => 2, 'ghost' => 0.5, 'dark' => 2, 'steel' => 0.5, 'fairy' => 0.5],
        'rock' => ['fire' => 2, 'ice' => 2, 'fighting' => 0.5, 'ground' => 0.5, 'flying' => 2, 'bug' => 2, 'steel' => 0.5],
        'ghost' => ['normal' => 0, 'psychic' => 2, 'ghost' => 2, 'dark' => 0.5],
        'dragon' => ['dragon' => 2, 'steel' => 0.5, 'fairy' => 0],
        'dark' => ['fighting' => 0.5, 'psychic' => 2, 'ghost' => 2, 'dark' => 0.5, 'fairy' => 0.5],
        'steel' => ['fire' => 0.5, 'water' => 0.5, 'electric' => 0.5, 'ice' => 2, 'rock' => 2, 'steel' => 0.5, 'fairy' => 2],
        'fairy' => ['fire' => 0.5, 'fighting' => 2, 'poison' => 0.5, 'dragon' => 2, 'dark' => 2, 'steel' => 0.5],
    ];

    /**
     * Calcular stats de un Pokémon según su nivel
     * Fórmulas oficiales de Pokémon (IVs fijos a 31, sin EVs)
     */
    public function calculateStatsForLevel($baseStats, $level)
    {
        $iv = 31;
        return [
            'hp' => floor((2 * $baseStats['hp'] + $iv) * $level / 100) + $level + 10,
            'attack' => floor(((2 * $baseStats['attack'] + $iv) * $level / 100) + 5),
            'defense' => floor(((2 * $baseStats['defense'] + $iv) * $level / 100) + 5),
            'special-attack' => floor(((2 * $baseStats['special-attack'] + $iv) * $level / 100) + 5),
            'special-defense' => floor(((2 * $baseStats['special-defense'] + $iv) * $level / 100) + 5),
            'speed' => floor(((2 * $baseStats['speed'] + $iv) * $level / 100) + 5),
        ];
    }

    /**
     * Calcular daño de un ataque con fórmula oficial completa
     */
    public function calculateDamage($attacker, $defender, $move, $level = 50)
    {
        $power = $move['power'] ?? 40;

        // Si el movimiento es de tipo estado (sin daño), retornar 0
        if (($move['damage_class'] ?? 'physical') === 'status' || $power <= 0) {
            return ['damage' => 0, 'effectiveness' => 1, 'critical' => false, 'missed' => false];
        }

        // Chequeo de accuracy
        $accuracy = $move['accuracy'] ?? 100;
        if ($accuracy < 100 && mt_rand(1, 100) > $accuracy) {
            return ['damage' => 0, 'effectiveness' => 1, 'critical' => false, 'missed' => true];
        }

        // Seleccionar ataque/defensa según clase de daño
        if (($move['damage_class'] ?? 'physical') === 'special') {
            $attack = $attacker['battle_stats']['special-attack'] ?? $attacker['stats']['special-attack'];
            $defense = $defender['battle_stats']['special-defense'] ?? $defender['stats']['special-defense'];
        }
        else {
            $attack = $attacker['battle_stats']['attack'] ?? $attacker['stats']['attack'];
            $defense = $defender['battle_stats']['defense'] ?? $defender['stats']['defense'];
        }

        // Aplicar modificador de quemadura (reduce ataque físico)
        if (($attacker['status'] ?? null) === 'burn' && ($move['damage_class'] ?? 'physical') === 'physical') {
            $attack = floor($attack * 0.5);
        }

        // Aplicar modificadores de stats (boost de objetos X)
        $atkStage = $attacker['stat_stages']['attack'] ?? 0;
        $defStage = $defender['stat_stages']['defense'] ?? 0;
        if (($move['damage_class'] ?? 'physical') === 'special') {
            $atkStage = $attacker['stat_stages']['special-attack'] ?? 0;
            $defStage = $defender['stat_stages']['special-defense'] ?? 0;
        }
        $attack = $this->applyStatStage($attack, $atkStage);
        $defense = $this->applyStatStage($defense, $defStage);

        // Fórmula base de daño Pokémon
        $damage = (((2 * $level / 5 + 2) * $power * $attack / $defense) / 50) + 2;

        // Critical hit (1/16 probabilidad, ×1.5)
        $critical = mt_rand(1, 16) === 1;
        if ($critical) {
            $damage *= 1.5;
        }

        // Multiplicador aleatorio (85-100%)
        $random = mt_rand(85, 100) / 100;
        $damage *= $random;

        // STAB (Same Type Attack Bonus) ×1.5
        $attackerTypes = $attacker['types'] ?? [];
        $moveType = $move['type'] ?? 'normal';
        if (in_array($moveType, $attackerTypes)) {
            $damage *= 1.5;
        }

        // Efectividad de tipo
        $effectiveness = $this->getTypeEffectiveness($moveType, $defender['types'] ?? []);
        $damage *= $effectiveness;

        $damage = max(1, floor($damage));

        return [
            'damage' => $damage,
            'effectiveness' => $effectiveness,
            'critical' => $critical,
            'missed' => false,
        ];
    }

    /**
     * Movimiento "Forcejeo" cuando no tiene PP
     */
    public function getStruggleMove()
    {
        return [
            'name' => 'struggle',
            'name_es' => 'Forcejeo',
            'power' => 50,
            'accuracy' => 100,
            'pp' => 999,
            'current_pp' => 999,
            'type' => 'normal',
            'damage_class' => 'physical',
            'status_effect' => null,
            'status_chance' => 0,
            'is_struggle' => true,
        ];
    }

    /**
     * Aplicar modificador de etapa de stat (-6 a +6)
     */
    private function applyStatStage($baseStat, $stage)
    {
        $stage = max(-6, min(6, $stage));
        if ($stage >= 0) {
            return floor($baseStat * (2 + $stage) / 2);
        }
        else {
            return floor($baseStat * 2 / (2 + abs($stage)));
        }
    }

    /**
     * Calcular efectividad de tipo
     */
    public function getTypeEffectiveness($moveType, $defenderTypes)
    {
        $effectiveness = 1;
        foreach ($defenderTypes as $defenderType) {
            if (isset($this->typeChart[$moveType][$defenderType])) {
                $effectiveness *= $this->typeChart[$moveType][$defenderType];
            }
        }
        return $effectiveness;
    }

    /**
     * Verificar si un Pokémon puede continuar
     */
    public function canContinue($pokemon)
    {
        return ($pokemon['current_hp'] ?? 0) > 0;
    }

    /**
     * Generar equipo aleatorio con movimientos reales
     */
    public function generateRandomTeam($size = 3, $level = 50)
    {
        $team = [];
        $usedIds = [];
        for ($i = 0; $i < $size; $i++) {
            // Evitar duplicados en el equipo
            do {
                $randomId = rand(1, 1025);
            } while (in_array($randomId, $usedIds));
            $usedIds[] = $randomId;

            $pokemon = PokemonHelper::getPokemon($randomId);
            if ($pokemon) {
                // Calcular stats para el nivel
                $battleStats = $this->calculateStatsForLevel($pokemon['stats'], $level);
                $pokemon['level'] = $level;
                $pokemon['battle_stats'] = $battleStats;
                $pokemon['current_hp'] = $battleStats['hp'];
                $pokemon['max_hp'] = $battleStats['hp'];
                $pokemon['moves'] = PokemonHelper::selectBattleMoves($randomId, 4, $level);
                $pokemon['status'] = null;
                $pokemon['status_turns'] = 0;
                $pokemon['stat_stages'] = [
                    'attack' => 0, 'defense' => 0,
                    'special-attack' => 0, 'special-defense' => 0,
                    'speed' => 0,
                ];
                $team[] = $pokemon;
            }
        }
        return $team;
    }

    /**
     * Preparar Pokémon del jugador para batalla
     */
    public function preparePokemonForBattle($pokemon, $level = 50)
    {
        $battleStats = $this->calculateStatsForLevel($pokemon['stats'], $level);
        $pokemon['level'] = $level;
        $pokemon['battle_stats'] = $battleStats;
        $pokemon['current_hp'] = $battleStats['hp'];
        $pokemon['max_hp'] = $battleStats['hp'];
        $pokemon['moves'] = PokemonHelper::selectBattleMoves($pokemon['id'], 4, $level);
        $pokemon['status'] = null;
        $pokemon['status_turns'] = 0;
        $pokemon['stat_stages'] = [
            'attack' => 0, 'defense' => 0,
            'special-attack' => 0, 'special-defense' => 0,
            'speed' => 0,
        ];
        return $pokemon;
    }

    /**
     * Verificar victoria (todo el equipo derrotado)
     */
    public function checkVictory($team)
    {
        foreach ($team as $pokemon) {
            if ($this->canContinue($pokemon)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtener mensaje de efectividad en español
     */
    public function getEffectivenessMessage($effectiveness)
    {
        if ($effectiveness >= 2) {
            return '¡Es súper eficaz!';
        }
        elseif ($effectiveness > 0 && $effectiveness < 1) {
            return 'No es muy eficaz...';
        }
        elseif ($effectiveness == 0) {
            return 'No afecta al Pokémon rival...';
        }
        return null;
    }
}