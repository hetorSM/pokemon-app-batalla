try {
echo "Attempting to fetch 'transform' (not in DB)...\n";
$move = \App\Helpers\PokemonHelper::selectBattleMoves(132, 1); // Ditto (Transform)
print_r($move);

echo "\nChecking DB for 'transform'...\n";
$dbMove = \App\Models\Move::where('name', 'transform')->first();
if ($dbMove) {
echo "SUCCESS: 'transform' found in DB!\n";
print_r($dbMove->toArray());
} else {
echo "FAILURE: 'transform' NOT found in DB.\n";
}

} catch (\Exception $e) {
echo "Error: " . $e->getMessage();
}