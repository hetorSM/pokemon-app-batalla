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
            // Use the Helper to get (cached) or fetch (API) the move
            // This leverages the "getOrFetchMove" logic we built!
            // We need to expose it publicly or wrap it. 
            // PokemonHelper::getOrFetchMove is private. We should make it public or use reflection?
            // Better: Make it public in Helper.

            // For now, assuming we will update Helper to be public or use a wrapper.
            // Let's modify PokemonHelper to make `getOrFetchMove` public for this usage.

            // Re-using reflection if we can't edit file easily? No, I can edit the file.
            // I will edit PokemonHelper::getOrFetchMove to be public.
            $moveData = PokemonHelper::getOrFetchMove($name);
            $results[$name] = $moveData;
        }

        return response()->json($results);
    }
}