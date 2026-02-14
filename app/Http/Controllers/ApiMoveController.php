<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\PokemonHelper;
use Illuminate\Support\Facades\Log;

class ApiMoveController extends Controller
{
    public function batchFetch(Request $request)
    {
        $moveNames = $request->input('moves', []);
        $results = [];

        foreach ($moveNames as $name) {
            // Usar el Helper para obtener (caché) o buscar (API) el movimiento
            // ¡Esto aprovecha la lógica "getOrFetchMove" que construimos!
            $moveData = PokemonHelper::getOrFetchMove($name);
            $results[$name] = $moveData;
        }

        return response()->json($results);
    }
}