<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Move;
use App\Helpers\MoveDatabase;

class MovesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moves = MoveDatabase::ALL_MOVES;

        foreach ($moves as $key => $data) {
            Move::updateOrCreate(
            ['name' => $key],
            [
                'name_es' => $data['name_es'],
                'power' => $data['power'],
                'accuracy' => $data['accuracy'],
                'pp' => $data['pp'],
                'type' => $data['type'],
                'damage_class' => $data['damage_class'],
                'status_effect' => $data['status_effect'],
                'status_chance' => $data['status_chance'],
            ]
            );
        }
    }
}