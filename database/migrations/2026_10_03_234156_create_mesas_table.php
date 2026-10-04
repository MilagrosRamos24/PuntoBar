<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->unique();
            $table->string('estado')->default('libre');
            $table->foreignId('mozo_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->integer('cantidad_personas')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};