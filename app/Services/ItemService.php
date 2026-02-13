<?php

namespace App\Services;

class ItemService
{
    /**
     * Catálogo de objetos de batalla disponibles
     */
    private $items = [
        // === CURACIÓN ===
        1 => [
            'id' => 1, 'name' => 'Poción', 'name_en' => 'potion',
            'category' => 'healing', 'effect' => 'heal', 'value' => 20,
            'description' => 'Restaura 20 PS de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/potion.png',
        ],
        2 => [
            'id' => 2, 'name' => 'Superpoción', 'name_en' => 'super-potion',
            'category' => 'healing', 'effect' => 'heal', 'value' => 50,
            'description' => 'Restaura 50 PS de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/super-potion.png',
        ],
        3 => [
            'id' => 3, 'name' => 'Hiperpoción', 'name_en' => 'hyper-potion',
            'category' => 'healing', 'effect' => 'heal', 'value' => 120,
            'description' => 'Restaura 120 PS de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/hyper-potion.png',
        ],
        4 => [
            'id' => 4, 'name' => 'Restaurar Todo', 'name_en' => 'max-potion',
            'category' => 'healing', 'effect' => 'heal_full', 'value' => 0,
            'description' => 'Restaura todos los PS de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/max-potion.png',
        ],
        // === CURACIÓN DE ESTADOS ===
        5 => [
            'id' => 5, 'name' => 'Antiquemar', 'name_en' => 'burn-heal',
            'category' => 'status_cure', 'effect' => 'cure_status', 'value' => 'burn',
            'description' => 'Cura la quemadura de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/burn-heal.png',
        ],
        6 => [
            'id' => 6, 'name' => 'Antiparaliz', 'name_en' => 'paralyze-heal',
            'category' => 'status_cure', 'effect' => 'cure_status', 'value' => 'paralyze',
            'description' => 'Cura la parálisis de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/paralyze-heal.png',
        ],
        7 => [
            'id' => 7, 'name' => 'Antihielo', 'name_en' => 'ice-heal',
            'category' => 'status_cure', 'effect' => 'cure_status', 'value' => 'freeze',
            'description' => 'Cura el congelamiento de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/ice-heal.png',
        ],
        8 => [
            'id' => 8, 'name' => 'Despertar', 'name_en' => 'awakening',
            'category' => 'status_cure', 'effect' => 'cure_status', 'value' => 'sleep',
            'description' => 'Despierta a un Pokémon dormido.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/awakening.png',
        ],
        9 => [
            'id' => 9, 'name' => 'Antídoto', 'name_en' => 'antidote',
            'category' => 'status_cure', 'effect' => 'cure_status', 'value' => 'poison',
            'description' => 'Cura el envenenamiento de un Pokémon.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/antidote.png',
        ],
        10 => [
            'id' => 10, 'name' => 'Cura Total', 'name_en' => 'full-heal',
            'category' => 'status_cure', 'effect' => 'cure_all', 'value' => null,
            'description' => 'Cura cualquier estado alterado.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/full-heal.png',
        ],
        // === REVIVIR ===
        11 => [
            'id' => 11, 'name' => 'Revivir', 'name_en' => 'revive',
            'category' => 'revive', 'effect' => 'revive', 'value' => 50,
            'description' => 'Revive un Pokémon debilitado con 50% de PS.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/revive.png',
        ],
        12 => [
            'id' => 12, 'name' => 'Revivir Máximo', 'name_en' => 'max-revive',
            'category' => 'revive', 'effect' => 'revive_full', 'value' => 100,
            'description' => 'Revive un Pokémon debilitado con todos sus PS.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/max-revive.png',
        ],
        // === MEJORAS DE STATS ===
        13 => [
            'id' => 13, 'name' => 'Ataque X', 'name_en' => 'x-attack',
            'category' => 'stat_boost', 'effect' => 'boost_stat', 'value' => 'attack',
            'description' => 'Aumenta el Ataque del Pokémon activo.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/x-attack.png',
        ],
        14 => [
            'id' => 14, 'name' => 'Defensa X', 'name_en' => 'x-defense',
            'category' => 'stat_boost', 'effect' => 'boost_stat', 'value' => 'defense',
            'description' => 'Aumenta la Defensa del Pokémon activo.',
            'sprite' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/x-defense.png',
        ],
    ];

    /**
     * Obtener lista de objetos disponibles para selección
     */
    public function getAvailableItems()
    {
        return $this->items;
    }

    /**
     * Obtener un objeto por ID
     */
    public function getItem($id)
    {
        return $this->items[$id] ?? null;
    }

    /**
     * Verificar si un objeto se puede usar sobre un Pokémon
     */
    public function canUseItem($itemId, $pokemon)
    {
        $item = $this->getItem($itemId);
        if (!$item)
            return false;

        switch ($item['effect']) {
            case 'heal':
            case 'heal_full':
                // Solo si tiene daño y no está debilitado
                return ($pokemon['current_hp'] > 0) &&
                    ($pokemon['current_hp'] < ($pokemon['max_hp'] ?? $pokemon['battle_stats']['hp']));

            case 'cure_status':
                // Solo si tiene ese estado
                if ($item['value'] === 'poison') {
                    return in_array($pokemon['status'] ?? null, ['poison', 'badly_poison']);
                }
                return ($pokemon['status'] ?? null) === $item['value'];

            case 'cure_all':
                return !empty($pokemon['status']);

            case 'revive':
            case 'revive_full':
                return ($pokemon['current_hp'] ?? 0) <= 0;

            case 'boost_stat':
                // Solo si el stage no está al máximo (+6)
                $stat = $item['value'];
                return ($pokemon['stat_stages'][$stat] ?? 0) < 6;

            default:
                return false;
        }
    }

    /**
     * Usar un objeto sobre un Pokémon
     * Retorna array con resultado y mensaje
     */
    public function useItem($itemId, &$pokemon)
    {
        $item = $this->getItem($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Objeto no encontrado.'];
        }

        if (!$this->canUseItem($itemId, $pokemon)) {
            return ['success' => false, 'message' => 'No se puede usar este objeto ahora.'];
        }

        $maxHp = $pokemon['max_hp'] ?? $pokemon['battle_stats']['hp'] ?? $pokemon['stats']['hp'];

        switch ($item['effect']) {
            case 'heal':
                $healAmount = min($item['value'], $maxHp - $pokemon['current_hp']);
                $pokemon['current_hp'] = min($maxHp, $pokemon['current_hp'] + $item['value']);
                return [
                    'success' => true,
                    'message' => "¡{$pokemon['name']} recupera {$healAmount} PS con {$item['name']}!",
                ];

            case 'heal_full':
                $healAmount = $maxHp - $pokemon['current_hp'];
                $pokemon['current_hp'] = $maxHp;
                return [
                    'success' => true,
                    'message' => "¡{$pokemon['name']} recupera todos sus PS con {$item['name']}!",
                ];

            case 'cure_status':
                $pokemon['status'] = null;
                $pokemon['status_turns'] = 0;
                return [
                    'success' => true,
                    'message' => "¡{$pokemon['name']} se ha curado con {$item['name']}!",
                ];

            case 'cure_all':
                $pokemon['status'] = null;
                $pokemon['status_turns'] = 0;
                return [
                    'success' => true,
                    'message' => "¡{$pokemon['name']} se ha curado completamente con {$item['name']}!",
                ];

            case 'revive':
                $pokemon['current_hp'] = max(1, floor($maxHp * $item['value'] / 100));
                $pokemon['status'] = null;
                $pokemon['status_turns'] = 0;
                return [
                    'success' => true,
                    'message' => "¡{$pokemon['name']} ha revivido con {$item['name']}!",
                ];

            case 'revive_full':
                $pokemon['current_hp'] = $maxHp;
                $pokemon['status'] = null;
                $pokemon['status_turns'] = 0;
                return [
                    'success' => true,
                    'message' => "¡{$pokemon['name']} ha revivido completamente con {$item['name']}!",
                ];

            case 'boost_stat':
                $stat = $item['value'];
                $pokemon['stat_stages'][$stat] = min(6, ($pokemon['stat_stages'][$stat] ?? 0) + 1);
                $statNames = [
                    'attack' => 'Ataque', 'defense' => 'Defensa',
                    'special-attack' => 'At. Especial', 'special-defense' => 'Def. Especial',
                    'speed' => 'Velocidad',
                ];
                $statName = $statNames[$stat] ?? $stat;
                return [
                    'success' => true,
                    'message' => "¡El {$statName} de {$pokemon['name']} ha subido con {$item['name']}!",
                ];

            default:
                return ['success' => false, 'message' => 'Efecto no implementado.'];
        }
    }
}