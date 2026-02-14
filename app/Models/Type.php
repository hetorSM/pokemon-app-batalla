<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $fillable = ['name'];

    public function pokemons()
    {
        return $this->belongsToMany(Pokemon::class , 'pokemon_types')->withPivot('slot');
    }
}