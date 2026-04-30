<?php

namespace App\Services;

use App\Models\Notification;

/**
 * Service pour la gestion et l'envoi des notifications internes
 */
class NotificationService
{
    /**
     * Envoie une notification générique
     */
    public static function send($user_id, $titre, $message, $type)
    {
        return Notification::create([
            'user_id' => $user_id,
            'titre'   => $titre,
            'message' => $message,
            'lu'      => false,
            'type'    => $type,
        ]);
    }

    /**
     * Notifie un vendeur d'une nouvelle commande
     */
    public static function nouvelleCommande($vendeur_id, $commande)
    {
        $titre = "Nouvelle commande reçue";
        $message = "La commande #{$commande->id} a été reçue pour votre boutique.";
        return self::send($vendeur_id, $titre, $message, 'commande');
    }

    /**
     * Notifie un client du changement de statut de sa commande
     */
    public static function statutCommande($client_id, $commande)
    {
        $titre = "Mise à jour de votre commande";
        $message = "Le statut de votre commande #{$commande->id} est désormais : " . strtoupper($commande->statut);
        return self::send($client_id, $titre, $message, 'commande');
    }

    /**
     * Notifie un client de la validation de son paiement
     */
    public static function paiementValide($client_id, $paiement)
    {
        $titre = "Paiement validé";
        $message = "Votre paiement de {$paiement->montant} a été validé (Réf: {$paiement->reference}).";
        return self::send($client_id, $titre, $message, 'paiement');
    }

    /**
     * Notifie un vendeur de l'activation de sa boutique
     */
    public static function boutiqueActivee($vendeur_id, $boutique)
    {
        $titre = "Boutique activée";
        $message = "Félicitations ! Votre boutique '{$boutique->nom}' a été activée et est désormais visible par les clients.";
        return self::send($vendeur_id, $titre, $message, 'boutique');
    }
}
