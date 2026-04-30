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
        // Création de la table boutiques
        Schema::create('boutiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Lien vers l'utilisateur propriétaire
            $table->string('nom', 150); // Nom de la boutique
            $table->text('description')->nullable(); // Description de la boutique
            $table->string('logo')->nullable(); // URL du logo
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('inactif'); // État de la boutique
            $table->string('slug')->unique(); // Identifiant unique pour l'URL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boutiques');
    }
};
