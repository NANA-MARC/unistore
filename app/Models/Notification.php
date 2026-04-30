<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour la gestion des notifications
 */
class Notification extends Model
{
    use HasFactory;

    // Champs autorisés pour le remplissage de masse
    protected $fillable = [
        'user_id',
        'titre',
        'message',
        'lu',
        'type',
    ];

    /**
     * Conversion automatique des types
     */
    protected $casts = [
        'lu' => 'boolean',
    ];

    /**
     * Relation : La notification appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
