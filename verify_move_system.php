<?php

use App\Helpers\PokemonHelper;
use App\Models\Move;

echo "=== VERIFICACIÓN DEL SISTEMA DE MOVIMIENTOS ===\n";

// 1. Test Status Move: Magikarp (Splash)
echo "\n1. Testimonio: Magikarp (ID 129) - Esperando 'splash'\n";
// Ensure splash is not in DB if we want to test fetching (optional, but good)
// Move::where('name', 'splash')->delete(); 

$magikarpMoves = PokemonHelper::selectBattleMoves(129, 4);
$splash = null;
foreach ($magikarpMoves as $m) {
    if ($m['name'] === 'splash') {
        $splash = $m;
        break;
    }
}

if ($splash) {
    echo "[PASS] 'splash' encontrado.\n";
    echo "       Detalles: Coste PP: {$splash['pp']}, Tipo: {$splash['type']}\n";

    // Verify DB persistence
    $dbSplash = Move::where('name', 'splash')->first();
    if ($dbSplash) {
        echo "[PASS] 'splash' guardado en base de datos correctamente.\n";
    }
    else {
        echo "[FAIL] 'splash' NO se guardó en la base de datos.\n";
    }
}
else {
    echo "[FAIL] Magikarp no aprendió 'splash'.\n";
    print_r($magikarpMoves);
}

// 2. Test Damage Move: Poliwag (Bubble)
// 'bubble' is likely not in our hardcoded list (we checked).
echo "\n2. Testimonio: Poliwag (ID 60) - Esperando 'bubble'\n";
// Move::where('name', 'bubble')->delete();

$poliwagMoves = PokemonHelper::selectBattleMoves(60, 4);
$bubble = null;
foreach ($poliwagMoves as $m) {
    if ($m['name'] === 'bubble') {
        $bubble = $m;
        break;
    }
}

if ($bubble) {
    echo "[PASS] 'bubble' encontrado.\n";
    echo "       Detalles: Poder: {$bubble['power']}, Clase: {$bubble['damage_class']}\n";

    // Verify usage logic attributes
    if ($bubble['power'] > 0 && $bubble['damage_class'] !== 'status') {
        echo "[PASS] 'bubble' identificado correctamente como ataque de daño.\n";
    }
    else {
        echo "[FAIL] 'bubble' tiene datos incorrectos para combate.\n";
    }

    $dbBubble = Move::where('name', 'bubble')->first();
    if ($dbBubble) {
        echo "[PASS] 'bubble' persistido en DB.\n";
    }
}
else {
    echo "[INFO] Poliwag no aprendió 'bubble' (puede que haya elegido otros mejores).\n";
// Check if any move was fetched from API (checking DB count maybe?)
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";