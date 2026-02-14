<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    protected $table = 'pokemons';
    protected $fillable = ['api_id', 'name', 'base_experience', 'height', 'weight'];

    // Relationships
    public function types()
    {
        return $this->belongsToMany(Type::class , 'pokemon_types')->withPivot('slot')->orderBy('pivot_slot');
    }

    public function stats()
    {
        return $this->belongsToMany(Stat::class , 'pokemon_stats')->withPivot('base_value');
    }

    public function moves()
    {
        return $this->belongsToMany(Move::class , 'pokemon_moves')
            ->withPivot('level_learned_at', 'learn_method')
            ->withTimestamps();
    }

    public function sprite()
    {
        return $this->hasOne(PokemonSprite::class);
    }

    public function cry()
    {
        return $this->hasOne(PokemonCry::class);
    }

    // Accessors for backward compatibility or ease of use
    public function getMoveListAttribute()
    {
        // Return a simplified list or the relation
        return $this->moves;
    }
}