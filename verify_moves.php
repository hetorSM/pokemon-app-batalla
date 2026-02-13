<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\PokemonHelper;
use Illuminate\Support\Facades\Cache;

$ids = [4]; // Charmander only for speed checking
echo "--- VERIFYING MOVES ---\n";

foreach ($ids as $id) {
    try {
        echo "\nChecking Pokemon ID: $id\n";
        $p = PokemonHelper::getPokemon($id);
        echo "Name: {$p['name']} | Types: " . implode(', ', $p['types']) . "\n";

        $moves = PokemonHelper::selectBattleMoves($id);
        echo "Selected Moves Count: " . count($moves) . "\n";

        if (empty($moves)) {
            echo "  [ERROR] NO MOVES FOUND! This causes 'Forcejeo'\n";
        }
        foreach ($moves as $m) {
            echo "  - {$m['name']} [{$m['type']}] (Pow: " . ($m['power'] ?? 'null') . ")\n";
        }
    }
    catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "\n--- END VERIFICATION ---\n";