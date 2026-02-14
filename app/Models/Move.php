<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_es',
        'power',
        'accuracy',
        'pp',
        'type',
        'damage_class', // physical, special, status
        'status_effect',
        'status_chance',
        'priority',
    ];

    /**
     * Get the formatted Move Array for the battle system.
     */
    public function toBattleArray()
    {
        return [
            'name' => $this->name,
            'name_es' => $this->name_es ?? ucfirst(str_replace('-', ' ', $this->name)),
            'power' => $this->power ?? 0,
            'accuracy' => $this->accuracy ?? 100,
            'pp' => $this->pp ?? 35,
            'current_pp' => $this->pp ?? 35,
            'type' => $this->type,
            'damage_class' => $this->damage_class,
            'status_effect' => $this->status_effect,
            'status_chance' => $this->status_chance,
            'priority' => $this->priority,
        ];
    }
}