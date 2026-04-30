<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modèle pour la gestion des commandes
 */
class Commande extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'user_id',
        'boutique_id',
        'statut',
        'total',
        'adresse_livraison',
    ];

    /**
     * Relation : La commande appartient à un utilisateur (client)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : La commande appartient à une boutique
     */
    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    /**
     * Relation : La commande contient plusieurs articles (items)
     */
    public function commandeItems(): HasMany
    {
        return $this->hasMany(CommandeItem::class);
    }

    /**
     * Relation : La commande possède un paiement
     */
    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class);
    }
}
