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
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_es')->nullable();
            $table->integer('power')->nullable();
            $table->integer('accuracy')->nullable();
            $table->integer('pp')->default(35);
            $table->string('type')->default('normal');
            $table->string('damage_class')->default('physical');
            $table->string('status_effect')->nullable();
            $table->integer('status_chance')->default(0);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};