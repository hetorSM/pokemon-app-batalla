<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    protected $table = 'pokemons';
    protected $fillable = ['api_id', 'name', 'sprites', 'types', 'stats', 'move_list', 'cries'];

    protected $casts = [
        'sprites' => 'array',
        'types' => 'array',
        'stats' => 'array',
        'move_list' => 'array',
        'cries' => 'array',
    ];
}