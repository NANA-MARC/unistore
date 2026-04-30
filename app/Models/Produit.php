<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle pour la gestion des produits
 */
class Produit extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'boutique_id',
        'categorie_id',
        'nom',
        'description',
        'prix',
        'stock',
        'images',
        'statut',
    ];

    /**
     * Conversion automatique des types (JSON vers Array pour les images)
     */
    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Relation : Le produit appartient à une boutique
     */
    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    /**
     * Relation : Le produit appartient à une catégorie
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * Relation : Le produit possède plusieurs avis
     */
    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }

    /**
     * Relation : Le produit peut être présent dans plusieurs paniers
     */
    public function panierItems(): HasMany
    {
        return $this->hasMany(Panier::class);
    }

    /**
     * Relation : Le produit peut figurer dans plusieurs lignes de commande
     */
    public function commandeItems(): HasMany
    {
        return $this->hasMany(CommandeItem::class);
    }
}
