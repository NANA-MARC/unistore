<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle pour la gestion des boutiques
 */
class Boutique extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'user_id',
        'nom',
        'description',
        'logo',
        'statut',
        'slug',
    ];

    /**
     * Relation : La boutique appartient à un utilisateur (propriétaire)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : La boutique possède plusieurs catégories
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Categorie::class);
    }

    /**
     * Relation : La boutique possède plusieurs produits
     */
    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class);
    }

    /**
     * Relation : La boutique a reçu plusieurs commandes
     */
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }
}
