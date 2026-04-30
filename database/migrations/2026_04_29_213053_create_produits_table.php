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
        // Création de la table produits
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade'); // Lien vers la boutique
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade'); // Lien vers la catégorie
            $table->string('nom', 200); // Nom du produit
            $table->text('description')->nullable(); // Description du produit
            $table->decimal('prix', 10, 2); // Prix du produit
            $table->integer('stock')->default(0); // Quantité en stock
            $table->json('images')->nullable(); // Liste d'images (format JSON)
            $table->enum('statut', ['actif', 'inactif'])->default('actif'); // État du produit
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
