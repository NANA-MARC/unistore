<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour la gestion des articles du panier
 */
class Panier extends Model
{
    use HasFactory;

    // Nom de la table si différent du pluriel par défaut (paniers est standard)
    protected $table = 'paniers';

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'user_id',
        'produit_id',
        'quantite',
    ];

    /**
     * Relation : L'item du panier appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : L'item du panier concerne un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
