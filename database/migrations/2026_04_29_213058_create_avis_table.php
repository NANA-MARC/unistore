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
        // Création de la table avis
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Auteur de l'avis
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade'); // Produit noté
            $table->integer('note'); // Note de 1 à 5
            $table->text('commentaire')->nullable(); // Commentaire optionnel
            $table->timestamps();
            
            // Un utilisateur ne peut laisser qu'un seul avis par produit
            $table->unique(['user_id', 'produit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
