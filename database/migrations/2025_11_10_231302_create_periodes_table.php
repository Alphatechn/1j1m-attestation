<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->string('libelle'); // "Décembre 2025 à Mars 2026"
            $table->string('mois_debut'); // "01" à "12"
            $table->string('annee_debut'); // "2025"
            $table->string('mois_fin');
            $table->string('annee_fin');
            $table->boolean('is_active')->default(true);
            $table->date('date_debut')->nullable(); // Date calculée
            $table->date('date_fin')->nullable(); // Date calculée
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('is_active');
            $table->index(['annee_debut', 'mois_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodes');
    }
};
