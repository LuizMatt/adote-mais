<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('species', ['dog', 'cat', 'other']);
            $table->enum('size', ['small', 'medium', 'large']);
            $table->string('approximate_age');
            $table->enum('gender', ['male', 'female']);
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->json('vaccines')->nullable();
            $table->enum('status', ['available', 'in_process', 'adopted'])->default('available');
            $table->date('rescue_date')->nullable();
            $table->string('adopter_name')->nullable();
            $table->date('adoption_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
