<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\PokemonHelper;
use App\Models\Pokemon;
use App\Models\Move;

class TestNormalize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar normalización de BD y Helper';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando prueba de normalización...");

        // 1. Fetch Charizard (ID 6)
        $this->info("1. Obteniendo Charizard (ID 6)...");
        $charizard = PokemonHelper::getPokemon(6);

        if (!$charizard) {
            $this->error("Falló al obtener Charizard.");
            return;
        }

        $this->info("Nombre: " . $charizard['name']);

        // Check Types
        $this->info("Tipos: " . implode(', ', $charizard['types']));
        if (empty($charizard['types']))
            $this->error("¡Tipos vacíos!");

        // Check Stats
        $this->info("Stats count: " . count($charizard['stats']));
        if (empty($charizard['stats']))
            $this->error("¡Stats vacíos!");

        // Check Moves
        $this->info("Movimientos count: " . count($charizard['moves_detailed']));

        if (!empty($charizard['moves_detailed'])) {
            $firstMove = $charizard['moves_detailed'][0];
            $this->info("Ejemplo de movimiento: " . $firstMove['name'] . " (Nivel: " . $firstMove['level'] . ", Método: " . $firstMove['method'] . ")");
        }
        else {
            $this->error("¡Movimientos vacíos!");
        }

        // Check Sprites
        $this->info("Imagen: " . $charizard['image']);

        // Check Cries
        $this->info("Grito Latest: " . ($charizard['cries']['latest'] ?? 'N/A'));

        // 2. Check Database Integrity
        $p = Pokemon::with(['types', 'stats', 'moves'])->find($charizard['id']);
        $this->info("DB Check - Relación Moves count: " . $p->moves()->count());

        // 3. Test Battle Moves Selection
        $this->info("3. Probando selección de movimientos para batalla (Nivel 50)...");
        $battleMoves = PokemonHelper::selectBattleMoves(6, 4, 50);
        foreach ($battleMoves as $bm) {
            $this->info("- " . $bm['name'] . " (Poder: " . $bm['power'] . ")");
        }

        // 4. Debug specific move if needed
        $moveName = 'mega-punch';
        $move = Move::where('name', $moveName)->first();
        if ($move) {
            $this->info("Debug '$moveName': Power=" . $move->power . ", NameES=" . $move->name_es);
        }
        else {
            $this->info("Debug '$moveName': No encontrado en BD.");
        }

        $this->info("¡Prueba completada!");
    }
}