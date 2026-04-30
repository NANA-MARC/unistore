<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle pour la gestion des catégories de produits
 */
class Categorie extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'boutique_id',
        'nom',
        'image',
    ];

    /**
     * Relation : La catégorie appartient à une boutique
     */
    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    /**
     * Relation : La catégorie contient plusieurs produits
     */
    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class);
    }
}
