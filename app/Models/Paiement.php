<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour la gestion des paiements
 */
class Paiement extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'commande_id',
        'methode',
        'statut',
        'montant',
        'reference',
    ];

    /**
     * Relation : Le paiement est lié à une commande
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
