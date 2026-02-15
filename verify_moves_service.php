<?php

use App\Services\BattleService;
use App\Helpers\PokemonHelper;
use Illuminate\Support\Facades\App;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = App::make(BattleService::class);

// Mock a pokemon (Charizard id 6)
$pokemon = PokemonHelper::getPokemon(6);

echo "Testing preparePokemonForBattle with default moves...\n";
$p1 = $service->preparePokemonForBattle($pokemon, 50);
echo "Default moves count: " . count($p1['moves']) . "\n";
foreach ($p1['moves'] as $m)
    echo "- {$m['name']}\n";

echo "\nTesting preparePokemonForBattle with CUSTOM moves...\n";
$customMoves = ['scratch', 'ember', 'dragon-breath']; // Less than 4, and specific
$p2 = $service->preparePokemonForBattle($pokemon, 50, $customMoves);

echo "Custom moves count: " . count($p2['moves']) . "\n";
foreach ($p2['moves'] as $m)
    echo "- {$m['name']}\n";

if (count($p2['moves']) === 3 && $p2['moves'][0]['name'] === 'scratch') {
    echo "\nSUCCESS: Custom moves applied correctly!\n";
}
else {
    echo "\nFAILURE: Custom moves not applied correctly.\n";
}