<?php

return [
    'effectiveness' => [
        'super_effective' => '¡Es súper eficaz!',
        'not_very_effective' => 'No es muy eficaz...',
        'no_effect' => 'No afecta al Pokémon rival...',
    ],
    'actions' => [
        'struggle' => '¡:attacker no tiene movimientos! Usa Forcejeo.',
        'no_pp' => '¡:attacker no tiene PP! Usa Forcejeo.',
        'switch' => '¡:attacker cambia a :target!',
        'switch_send' => '¡Envías a :pokemon!',
        'opponent_send' => ':trainer envía a :pokemon!',
        'item_used' => '¡Has usado :item en :target!',
    ],
    'combat' => [
        'missed' => '¡:attacker usó :move, pero falló!',
        'used_move' => '¡:attacker usó :move! (-:damage HP)',
        'critical_hit' => '¡Golpe crítico!',
        'recoil' => '¡:attacker recibe :damage de daño de retroceso!',
        'fainted' => '¡:pokemon se ha debilitado!',
        'fainted_recoil' => '¡:attacker se ha debilitado por el retroceso!',
        'fainted_status' => '¡:pokemon se ha debilitado por el estado alterado!',
        'win' => '¡:winner ha ganado!',
        'ai_thinking' => 'La IA está pensando...',
        'ai_error' => 'Error de IA. Recargando...',
    ],
    'errors' => [
        'no_pp_move' => 'Ese movimiento no tiene PP disponible.',
        'cannot_use_item' => 'No se puede usar este objeto ahora.',
        'item_not_found' => 'No tienes ese objeto.',
        'cannot_switch' => 'No puedes cambiar a ese Pokémon.',
        'invalid_action' => 'Acción no válida.',
        'not_ai_turn' => 'No es el turno de la IA',
    ],
];