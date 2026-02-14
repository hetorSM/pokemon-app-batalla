<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokemonCry extends Model
{
    protected $fillable = ['pokemon_id', 'latest', 'legacy'];

    public function pokemon()
    {
        return $this->belongsTo(Pokemon::class);
    }
}