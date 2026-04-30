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
        // Création de la table commandes
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Client ayant passé la commande
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade'); // Boutique concernée
            $table->enum('statut', ['en_attente', 'confirmee', 'en_livraison', 'livree', 'annulee'])->default('en_attente'); // État de la commande
            $table->decimal('total', 10, 2); // Montant total de la commande
            $table->text('adresse_livraison'); // Adresse de livraison détaillée
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
