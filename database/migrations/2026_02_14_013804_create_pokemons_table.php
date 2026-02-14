<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pokemons', function (Blueprint $table) {
            $table->id(); // Auto-increment ID, but we will key it to PokeAPI ID if possible or just store 'api_id'
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->json('sprites')->nullable();
            $table->json('types')->nullable();
            $table->json('stats')->nullable();
            $table->json('move_list')->nullable(); // List of move names
            $table->json('cries')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemons');
    }
};