<?php

namespace App\Services;

class AIService
{
    private $battleService;

    public function __construct(BattleService $battleService)
    {
        $this->battleService = $battleService;
    }

    /**
     * Decidir acción de la IA
     */
    public function decideAction($aiTeam, $playerTeam, $aiActiveIndex, $playerActiveIndex, $difficulty = 'normal', $aiItems = [])
    {
        $aiPokemon = $aiTeam[$aiActiveIndex];
        $playerPokemon = $playerTeam[$playerActiveIndex];

        switch ($difficulty) {
            case 'easy':
                return $this->easyAI($aiPokemon);
            case 'normal':
                return $this->normalAI($aiPokemon, $playerPokemon, $aiTeam, $aiActiveIndex);
            case 'hard':
                return $this->hardAI($aiTeam, $playerTeam, $aiActiveIndex, $playerActiveIndex, $aiItems);
            default:
                return $this->normalAI($aiPokemon, $playerPokemon, $aiTeam, $aiActiveIndex);
        }
    }

    /**
     * IA fácil - Elige movimiento aleatorio
     */
    private function easyAI($aiPokemon)
    {
        $moves = $aiPokemon['moves'] ?? [];
        $availableMoves = $this->getAvailableMoves($moves);

        if (empty($availableMoves)) {
            return ['action' => 'move', 'move_index' => -1]; // Forcejeo
        }

        $randomIndex = array_rand($availableMoves);
        return [
            'action' => 'move',
            'move_index' => $availableMoves[$randomIndex]['original_index'],
        ];
    }

    /**
     * IA normal - Considera tipos y efectividad
     */
    private function normalAI($aiPokemon, $playerPokemon, $aiTeam, $aiActiveIndex)
    {
        // Si tiene baja salud y hay otros Pokémon disponibles, considerar cambiar
        $hpRatio = $aiPokemon['current_hp'] / ($aiPokemon['max_hp'] ?? $aiPokemon['battle_stats']['hp']);
        if ($hpRatio < 0.25) {
            $switchTarget = $this->findBetterSwitch($aiTeam, $aiActiveIndex, $playerPokemon);
            if ($switchTarget !== false) {
                return ['action' => 'switch', 'target' => $switchTarget];
            }
        }

        // Elegir el mejor movimiento por efectividad
        $moves = $aiPokemon['moves'] ?? [];
        $availableMoves = $this->getAvailableMoves($moves);

        if (empty($availableMoves)) {
            return ['action' => 'move', 'move_index' => -1]; // Forcejeo
        }

        $bestMove = null;
        $bestScore = -1;

        foreach ($availableMoves as $moveData) {
            $move = $moveData['move'];
            $power = $move['power'] ?? 0;
            if ($power <= 0)
                continue;

            $effectiveness = $this->battleService->getTypeEffectiveness(
                $move['type'] ?? 'normal',
                $playerPokemon['types'] ?? []
            );

            // STAB bonus
            $stab = in_array($move['type'] ?? 'normal', $aiPokemon['types'] ?? []) ? 1.5 : 1;
            $score = $power * $effectiveness * $stab;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $moveData;
            }
        }

        if (!$bestMove) {
            $bestMove = $availableMoves[array_rand($availableMoves)];
        }

        return [
            'action' => 'move',
            'move_index' => $bestMove['original_index'],
        ];
    }

    /**
     * IA difícil - Estrategia avanzada con items y cambios inteligentes
     */
    private function hardAI($aiTeam, $playerTeam, $aiActiveIndex, $playerActiveIndex, $aiItems = [])
    {
        $aiPokemon = $aiTeam[$aiActiveIndex];
        $playerPokemon = $playerTeam[$playerActiveIndex];
        $hpRatio = $aiPokemon['current_hp'] / ($aiPokemon['max_hp'] ?? $aiPokemon['battle_stats']['hp']);

        // Prioridad 1: Usar objeto de curación si HP < 30% y tiene items
        if ($hpRatio < 0.30 && !empty($aiItems)) {
            $healItem = $this->findBestHealItem($aiItems, $aiPokemon);
            if ($healItem !== null) {
                return ['action' => 'item', 'item_id' => $healItem, 'target_index' => $aiActiveIndex];
            }
        }

        // Prioridad 2: Cambiar si en desventaja de tipo grave
        $bestPlayerEffectiveness = 0;
        foreach ($playerPokemon['types'] ?? [] as $pType) {
            $eff = $this->battleService->getTypeEffectiveness($pType, $aiPokemon['types'] ?? []);
            $bestPlayerEffectiveness = max($bestPlayerEffectiveness, $eff);
        }

        if ($bestPlayerEffectiveness >= 2 || $hpRatio < 0.2) {
            $switchTarget = $this->findBetterSwitch($aiTeam, $aiActiveIndex, $playerPokemon);
            if ($switchTarget !== false) {
                return ['action' => 'switch', 'target' => $switchTarget];
            }
        }

        // Prioridad 3: Elegir el mejor movimiento considerando todo
        $moves = $aiPokemon['moves'] ?? [];
        $availableMoves = $this->getAvailableMoves($moves);

        if (empty($availableMoves)) {
            return ['action' => 'move', 'move_index' => -1];
        }

        $bestMove = null;
        $bestScore = -1;

        foreach ($availableMoves as $moveData) {
            $move = $moveData['move'];
            $power = $move['power'] ?? 0;
            if ($power <= 0) {
                // Movimientos de estado podrían ser útiles pero los simplificamos
                continue;
            }

            $effectiveness = $this->battleService->getTypeEffectiveness(
                $move['type'] ?? 'normal',
                $playerPokemon['types'] ?? []
            );
            $stab = in_array($move['type'] ?? 'normal', $aiPokemon['types'] ?? []) ? 1.5 : 1;
            $accuracy = ($move['accuracy'] ?? 100) / 100;

            // Score = daño esperado
            $score = $power * $effectiveness * $stab * $accuracy;

            // Bonus por poder matar al oponente
            $estimatedDamage = $power * $effectiveness * $stab * 0.5; // Estimación rough
            $playerHpRatio = $playerPokemon['current_hp'] / ($playerPokemon['max_hp'] ?? $playerPokemon['battle_stats']['hp'] ?? 100);
            if ($playerHpRatio < 0.3) {
                $score *= 1.3; // Priorizar matar
            }

            // Bonus por efecto de estado si el oponente no tiene uno
            if (!empty($move['status_effect']) && empty($playerPokemon['status'])) {
                $score += 20;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $moveData;
            }
        }

        if (!$bestMove) {
            $bestMove = $availableMoves[array_rand($availableMoves)];
        }

        return [
            'action' => 'move',
            'move_index' => $bestMove['original_index'],
        ];
    }

    /**
     * Obtener movimientos con PP disponible
     */
    private function getAvailableMoves($moves)
    {
        $available = [];
        foreach ($moves as $index => $move) {
            if (($move['current_pp'] ?? 0) > 0) {
                $available[] = ['move' => $move, 'original_index' => $index];
            }
        }
        return $available;
    }

    /**
     * Encontrar un mejor Pokémon para cambiar
     */
    private function findBetterSwitch($team, $currentIndex, $opponent)
    {
        $bestIndex = false;
        $bestScore = -999;

        foreach ($team as $index => $pokemon) {
            if ($index === $currentIndex)
                continue;
            if (($pokemon['current_hp'] ?? 0) <= 0)
                continue;

            // Calcular ventaja de tipo
            $score = 0;
            foreach ($pokemon['types'] ?? [] as $type) {
                $score += $this->battleService->getTypeEffectiveness($type, $opponent['types'] ?? []);
            }
            // Penalizar si el oponente es efectivo contra este Pokémon
            foreach ($opponent['types'] ?? [] as $oType) {
                $score -= $this->battleService->getTypeEffectiveness($oType, $pokemon['types'] ?? []);
            }

            // Bonus por HP alto
            $hpRatio = $pokemon['current_hp'] / ($pokemon['max_hp'] ?? $pokemon['battle_stats']['hp'] ?? 100);
            $score += $hpRatio;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestScore > 0 ? $bestIndex : false;
    }

    /**
     * Encontrar el mejor item de curación para usar
     */
    private function findBestHealItem($items, $pokemon)
    {
        $missingHp = ($pokemon['max_hp'] ?? $pokemon['battle_stats']['hp']) - $pokemon['current_hp'];
        $bestItem = null;
        $bestValue = 0;

        foreach ($items as $key => $item) {
            if (($item['quantity'] ?? 0) <= 0)
                continue;
            if (($item['effect'] ?? '') !== 'heal' && ($item['effect'] ?? '') !== 'heal_full')
                continue;

            $healValue = ($item['effect'] === 'heal_full') ? $missingHp : min($item['value'] ?? 0, $missingHp);
            if ($healValue > $bestValue) {
                $bestValue = $healValue;
                $bestItem = $item['id'] ?? $key;
            }
        }

        return $bestItem;
    }
}