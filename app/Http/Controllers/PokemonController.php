<?php

namespace App\Http\Controllers;

use App\Helpers\PokemonHelper;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    public function index(Request $request)
    {
        $page = max(1, (int)$request->get('page', 1));
        $limit = 20;

        $data = PokemonHelper::getPokemonList($page, $limit);

        return view('pokemon.index', [
            'pokemons' => $data['pokemons'],
            'currentPage' => $data['current_page'],
            'totalPages' => $data['total_pages'],
            'hasNext' => $data['next'] !== null,
            'hasPrevious' => $data['previous'] !== null,
            'available_pokemon' => PokemonHelper::getSimplePokemonList()
        ]);
    }

    public function show(Request $request, $id)
    {
        $pokemon = PokemonHelper::getPokemon($id);
        $page = $request->get('from_page', 1); // Para volver a la página correcta

        if (!$pokemon) {
            return redirect()->route('pokemon.index')
                ->with('error', 'Pokémon no encontrado');
        }

        return view('pokemon.show', [
            'pokemon' => $pokemon,
            'fromPage' => $page
        ]);
    }


    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return redirect()->route('pokemon.index')
                ->with('error', 'Por favor, ingresa un nombre o ID de Pokémon');
        }

        $results = PokemonHelper::searchPokemon($query);

        if (empty($results)) {
            return redirect()->route('pokemon.index')
                ->with('error', "No se encontró ningún Pokémon con: '$query'");
        }

        // Si solo hay un resultado exacto, redirigir directamente
        if (count($results) === 1 && (strtolower($results[0]['name']) === strtolower($query) || $results[0]['id'] == $query)) {
            return redirect()->route('pokemon.show', $results[0]['id']);
        }

        return view('pokemon.index', [
            'pokemons' => $results,
            'currentPage' => 1,
            'totalPages' => 1,
            'hasNext' => false,
            'hasPrevious' => false,
            'searchQuery' => $query,
            'available_pokemon' => PokemonHelper::getSimplePokemonList()
        ]);
    }

    public function items(\App\Services\ItemService $itemService)
    {
        $items = $itemService->getAvailableItems();
        return view('pokedex.items', ['items' => $items]);
    }
}