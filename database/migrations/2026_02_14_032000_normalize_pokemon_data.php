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
        // 1. Crear Tabla Maestra de Tipos
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 2. Crear Tabla Maestra de Estadísticas
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 3. Crear Tabla Pivote de Tipos de Pokémon
        Schema::create('pokemon_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->onDelete('cascade');
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade');
            $table->integer('slot')->default(1);
            $table->timestamps();
        });

        // 4. Crear Tabla Pivote de Estadísticas de Pokémon
        Schema::create('pokemon_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->onDelete('cascade');
            $table->foreignId('stat_id')->constrained('stats')->onDelete('cascade');
            $table->integer('base_value');
            $table->timestamps();
        });

        // 5. Crear Tabla Pivote de Movimientos de Pokémon
        Schema::create('pokemon_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->onDelete('cascade');
            $table->foreignId('move_id')->constrained('moves')->onDelete('cascade');
            $table->integer('level_learned_at')->default(0);
            $table->string('learn_method')->default('level-up'); // level-up, machine, tutor, egg
            $table->timestamps();
        });

        // 6. Crear Tabla de Sprites de Pokémon
        Schema::create('pokemon_sprites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->onDelete('cascade');
            $table->text('front_default')->nullable();
            $table->text('official_artwork')->nullable();
            $table->text('front_shiny')->nullable();
            $table->text('back_default')->nullable();
            $table->timestamps();
        });

        // 7. Crear Tabla de Gritos de Pokémon
        Schema::create('pokemon_cries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemons')->onDelete('cascade');
            $table->text('latest')->nullable();
            $table->text('legacy')->nullable();
            $table->timestamps();
        });

        // 8. Modificar Tabla Pokemons (Eliminar columnas JSON)
        Schema::table('pokemons', function (Blueprint $table) {
            $table->dropColumn(['sprites', 'types', 'stats', 'move_list', 'cries']);
            $table->integer('base_experience')->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->json('sprites')->nullable();
            $table->json('types')->nullable();
            $table->json('stats')->nullable();
            $table->json('move_list')->nullable();
            $table->json('cries')->nullable();
            $table->dropColumn(['base_experience', 'height', 'weight']);
        });

        Schema::dropIfExists('pokemon_cries');
        Schema::dropIfExists('pokemon_sprites');
        Schema::dropIfExists('pokemon_moves');
        Schema::dropIfExists('pokemon_stats');
        Schema::dropIfExists('pokemon_types');
        Schema::dropIfExists('stats');
        Schema::dropIfExists('types');
    }
};