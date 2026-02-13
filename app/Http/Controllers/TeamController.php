<?php

namespace App\Http\Controllers;

use App\Helpers\PokemonHelper;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teamIds = session('team', []);
        $team = [];
        
        foreach ($teamIds as $id) {
            $pokemon = PokemonHelper::getPokemon($id);
            if ($pokemon) {
                $team[] = $pokemon;
            }
        }
        
        return view('team.index', [
            'team' => $team,
            'teamCount' => count($team)
        ]);
    }
    
    public function add(Request $request, $id)
    {
        // Verificar que el Pokémon existe
        $pokemon = PokemonHelper::getPokemon($id);
        if (!$pokemon) {
            return redirect()->back()
                ->with('error', 'Pokémon no encontrado');
        }
        
        $team = session('team', []);
        
        // Máximo 6 Pokémon
        if (count($team) >= 6) {
            return redirect()->back()
                ->with('error', '¡Tu equipo ya tiene 6 Pokémon! Elimina uno primero.');
        }
        
        // No duplicados
        if (in_array($id, $team)) {
            return redirect()->back()
                ->with('error', '¡Este Pokémon ya está en tu equipo!');
        }
        
        $team[] = $id;
        session(['team' => $team]);
        
        return redirect()->back()
            ->with('success', "¡{$pokemon['name']} añadido al equipo!");
    }
    
    public function remove(Request $request, $id)
    {
        $team = session('team', []);
        
        $index = array_search($id, $team);
        if ($index !== false) {
            unset($team[$index]);
            $team = array_values($team); // Reindexar
            session(['team' => $team]);
            
            return redirect()->back()
                ->with('success', '¡Pokémon eliminado del equipo!');
        }
        
        return redirect()->back()
            ->with('error', 'Pokémon no encontrado en el equipo');
    }
    
    public function clear()
    {
        session(['team' => []]);
        
        return redirect()->route('team.index')
            ->with('success', '¡Equipo vaciado correctamente!');
    }
    
    public function swap(Request $request)
    {
        $team = session('team', []);
        $position1 = $request->get('pos1');
        $position2 = $request->get('pos2');
        
        if (isset($team[$position1]) && isset($team[$position2])) {
            $temp = $team[$position1];
            $team[$position1] = $team[$position2];
            $team[$position2] = $temp;
            
            session(['team' => $team]);
            
            return redirect()->back()
                ->with('success', '¡Posiciones intercambiadas!');
        }
        
        return redirect()->back()
            ->with('error', 'Posiciones no válidas');
    }
}