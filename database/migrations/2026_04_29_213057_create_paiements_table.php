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
        // Création de la table paiements
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade'); // Commande associée
            $table->string('methode', 100); // Moyen de paiement (ex: Mobile Money, Carte)
            $table->enum('statut', ['en_attente', 'valide', 'echoue', 'rembourse'])->default('en_attente'); // État du paiement
            $table->decimal('montant', 10, 2); // Somme payée
            $table->string('reference')->unique(); // Référence de transaction unique
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
