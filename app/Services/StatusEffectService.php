<?php

namespace App\Services;

class StatusEffectService
{
    /**
     * Definiciones de estados alterados
     */
    private $statusDefinitions = [
        'burn' => [
            'name_es' => 'Quemado',
            'icon' => 'fa-fire',
            'color' => '#F08030',
            'badge_class' => 'bg-danger',
        ],
        'paralyze' => [
            'name_es' => 'Paralizado',
            'icon' => 'fa-bolt',
            'color' => '#F8D030',
            'badge_class' => 'bg-warning',
        ],
        'freeze' => [
            'name_es' => 'Congelado',
            'icon' => 'fa-snowflake',
            'color' => '#98D8D8',
            'badge_class' => 'bg-info',
        ],
        'sleep' => [
            'name_es' => 'Dormido',
            'icon' => 'fa-moon',
            'color' => '#A890F0',
            'badge_class' => 'bg-secondary',
        ],
        'poison' => [
            'name_es' => 'Envenenado',
            'icon' => 'fa-skull-crossbones',
            'color' => '#A040A0',
            'badge_class' => 'bg-purple',
        ],
        'badly_poison' => [
            'name_es' => 'Muy Envenenado',
            'icon' => 'fa-biohazard',
            'color' => '#700070',
            'badge_class' => 'bg-purple',
        ],
    ];

    /**
     * Intentar aplicar un estado alterado
     * Retorna array con resultado y mensaje
     */
    public function applyStatus(&$pokemon, $status, $chance = 100)
    {
        // No aplicar si ya tiene un estado
        if (!empty($pokemon['status'])) {
            return [
                'applied' => false,
                'message' => "{$pokemon['name']} ya tiene un estado alterado.",
            ];
        }

        // Chequear probabilidad
        if ($chance < 100 && mt_rand(1, 100) > $chance) {
            return [
                'applied' => false,
                'message' => null,
            ];
        }

        // Inmunidades de tipo
        $types = $pokemon['types'] ?? [];
        if ($status === 'burn' && in_array('fire', $types)) {
            return ['applied' => false, 'message' => null];
        }
        if (($status === 'poison' || $status === 'badly_poison') && (in_array('poison', $types) || in_array('steel', $types))) {
            return ['applied' => false, 'message' => null];
        }
        if ($status === 'paralyze' && in_array('electric', $types)) {
            return ['applied' => false, 'message' => null];
        }
        if ($status === 'freeze' && in_array('ice', $types)) {
            return ['applied' => false, 'message' => null];
        }

        // Mapear nombres de PokéAPI a nuestros nombres internos
        $statusMap = [
            'burn' => 'burn',
            'paralysis' => 'paralyze',
            'freeze' => 'freeze',
            'sleep' => 'sleep',
            'poison' => 'poison',
            'badly-poison' => 'badly_poison',
        ];
        $mappedStatus = $statusMap[$status] ?? $status;

        $pokemon['status'] = $mappedStatus;
        $pokemon['status_turns'] = 0;

        $def = $this->statusDefinitions[$mappedStatus] ?? null;
        $statusName = $def ? $def['name_es'] : $mappedStatus;

        return [
            'applied' => true,
            'message' => "¡{$pokemon['name']} ha sido {$statusName}!",
        ];
    }

    /**
     * Procesar efectos de estado al inicio del turno
     * Retorna array con mensajes y daño
     */
    public function processStatusEffects(&$pokemon)
    {
        $status = $pokemon['status'] ?? null;
        if (!$status) {
            return ['messages' => [], 'damage' => 0, 'can_act' => true];
        }

        $messages = [];
        $damage = 0;
        $canAct = true;
        $maxHp = $pokemon['max_hp'] ?? $pokemon['battle_stats']['hp'] ?? $pokemon['stats']['hp'];
        $pokemon['status_turns'] = ($pokemon['status_turns'] ?? 0) + 1;

        switch ($status) {
            case 'burn':
                // Daño = 1/16 HP máximo
                $damage = max(1, floor($maxHp / 16));
                $messages[] = "¡{$pokemon['name']} sufre por su quemadura! (-{$damage} HP)";
                break;

            case 'paralyze':
                // 25% de no poder moverse
                if (mt_rand(1, 4) === 1) {
                    $canAct = false;
                    $messages[] = "¡{$pokemon['name']} está paralizado! No puede moverse.";
                }
                break;

            case 'freeze':
                // 20% de descongelarse cada turno
                if (mt_rand(1, 5) === 1) {
                    $pokemon['status'] = null;
                    $pokemon['status_turns'] = 0;
                    $messages[] = "¡{$pokemon['name']} se ha descongelado!";
                }
                else {
                    $canAct = false;
                    $messages[] = "¡{$pokemon['name']} está congelado! No puede moverse.";
                }
                break;

            case 'sleep':
                // Duerme 1-3 turnos
                if ($pokemon['status_turns'] >= mt_rand(1, 3)) {
                    $pokemon['status'] = null;
                    $pokemon['status_turns'] = 0;
                    $messages[] = "¡{$pokemon['name']} se ha despertado!";
                }
                else {
                    $canAct = false;
                    $messages[] = "¡{$pokemon['name']} está dormido!";
                }
                break;

            case 'poison':
                // Daño = 1/8 HP máximo
                $damage = max(1, floor($maxHp / 8));
                $messages[] = "¡{$pokemon['name']} sufre por el veneno! (-{$damage} HP)";
                break;

            case 'badly_poison':
                // Daño creciente: turno/16 * HP máximo
                $turns = $pokemon['status_turns'];
                $damage = max(1, floor($maxHp * $turns / 16));
                $messages[] = "¡{$pokemon['name']} sufre gravemente por el veneno! (-{$damage} HP)";
                break;
        }

        // Aplicar daño de estado
        if ($damage > 0) {
            $pokemon['current_hp'] = max(0, $pokemon['current_hp'] - $damage);
        }

        return [
            'messages' => $messages,
            'damage' => $damage,
            'can_act' => $canAct,
        ];
    }

    /**
     * Curar un estado específico
     */
    public function cureStatus(&$pokemon, $statusToCure = null)
    {
        if (!$statusToCure) {
            // Curar cualquier estado
            $pokemon['status'] = null;
            $pokemon['status_turns'] = 0;
            return true;
        }

        if ($pokemon['status'] === $statusToCure) {
            $pokemon['status'] = null;
            $pokemon['status_turns'] = 0;
            return true;
        }

        return false;
    }

    /**
     * Obtener definición visual de un estado
     */
    public function getStatusDisplay($status)
    {
        return $this->statusDefinitions[$status] ?? null;
    }

    /**
     * Obtener todas las definiciones de estados
     */
    public function getAllStatusDefinitions()
    {
        return $this->statusDefinitions;
    }
}