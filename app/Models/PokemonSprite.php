<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokemonSprite extends Model
{
    protected $fillable = ['pokemon_id', 'front_default', 'official_artwork', 'front_shiny', 'back_default'];

    public function pokemon()
    {
        return $this->belongsTo(Pokemon::class);
    }
}