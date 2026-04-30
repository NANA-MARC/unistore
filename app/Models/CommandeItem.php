<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour la gestion des détails de commande
 */
class CommandeItem extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'commande_id',
        'produit_id',
        'quantite',
        'prix',
    ];

    /**
     * Relation : L'item appartient à une commande parente
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * Relation : L'item référence un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
