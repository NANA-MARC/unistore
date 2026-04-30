<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour la gestion des avis clients
 */
class Avis extends Model
{
    use HasFactory;

    // Nom de la table standard (avis est le pluriel d'avis en français, Laravel attend avis)
    protected $table = 'avis';

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'user_id',
        'produit_id',
        'note',
        'commentaire',
    ];

    /**
     * Relation : L'avis est rédigé par un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : L'avis concerne un produit spécifique
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
