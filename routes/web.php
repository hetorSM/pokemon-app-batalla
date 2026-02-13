<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\BattleController;

// Página de inicio
Route::get('/', function () {
    return view('welcome');
})->name('inicio');

// Pokédex
Route::get('/pokemon', [PokemonController::class , 'index'])->name('pokemon.index');
Route::get('/pokemon/{id}', [PokemonController::class , 'show'])->name('pokemon.show');
Route::get('/search', [PokemonController::class , 'search'])->name('pokemon.search');
Route::get('/items', [PokemonController::class , 'items'])->name('pokedex.items');

// Equipo
Route::prefix('team')->group(function () {
    Route::get('/', [TeamController::class , 'index'])->name('team.index');
    Route::post('/add/{id}', [TeamController::class , 'add'])->name('team.add');
    Route::delete('/remove/{id}', [TeamController::class , 'remove'])->name('team.remove');
    Route::delete('/clear', [TeamController::class , 'clear'])->name('team.clear');
    Route::post('/swap', [TeamController::class , 'swap'])->name('team.swap');
});

// Rutas de batalla
Route::prefix('battle')->group(function () {
    // Selección de modo
    Route::get('/', [BattleController::class , 'selectMode'])->name('battle.select-mode');

    // Configuración
    Route::get('/setup/ai', [BattleController::class , 'setupAI'])->name('battle.setup.ai');
    Route::get('/setup/multiplayer', [BattleController::class , 'setupMultiplayer'])->name('battle.setup.multiplayer');

    // Iniciar batalla
    Route::post('/start/ai', [BattleController::class , 'startAIBattle'])->name('battle.start.ai');
    Route::post('/start/multiplayer', [BattleController::class , 'startMultiplayerBattle'])->name('battle.start.multiplayer');

    // Arena de batalla
    Route::get('/arena', [BattleController::class , 'arena'])->name('battle.arena');

    // Acciones
    Route::post('/action', [BattleController::class , 'action'])->name('battle.action');
    Route::post('/ai-action', [BattleController::class , 'aiAction'])->name('battle.ai-action');

    // Finalizar
    Route::get('/finish', [BattleController::class , 'finish'])->name('battle.finish');
});