<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    protected $fillable = ['name'];

    public function pokemons()
    {
        return $this->belongsToMany(Pokemon::class , 'pokemon_stats')->withPivot('base_value');
    }
}