<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\Commande;
use App\Models\CommandeItem;
use App\Models\Panier;
use App\Models\Produit;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion des commandes
 */
class CommandeController extends Controller
{
    #[OA\Post(
        path: '/api/commandes',
        summary: 'Passer une commande',
        tags: ['Commandes'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['adresse_livraison', 'boutique_id'],
                properties: [
                    new OA\Property(property: 'adresse_livraison', type: 'string'),
                    new OA\Property(property: 'boutique_id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Commande créée'),
            new OA\Response(response: 400, description: 'Panier vide ou stock insuffisant')
        ]
    )]
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'adresse_livraison' => 'required|string',
            'boutique_id'       => 'required|exists:boutiques,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user_id = auth()->id();
        $boutique_id = $request->boutique_id;

        // Récupération des articles du panier pour cette boutique
        $panierItems = Panier::where('user_id', $user_id)
            ->whereHas('produit', function ($query) use ($boutique_id) {
                $query->where('boutique_id', $boutique_id);
            })
            ->with('produit')
            ->get();

        if ($panierItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide pour cette boutique',
            ], 400);
        }

        // Début de la transaction pour garantir l'intégrité des données
        return DB::transaction(function () use ($request, $user_id, $boutique_id, $panierItems) {
            $total = 0;

            // Vérification des stocks avant de commencer
            foreach ($panierItems as $item) {
                if ($item->produit->stock < $item->quantite) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour le produit : {$item->produit->nom}",
                    ], 400);
                }
            }

            // Création de la commande
            $commande = Commande::create([
                'user_id'           => $user_id,
                'boutique_id'       => $boutique_id,
                'statut'            => 'en_attente',
                'total'             => 0, // Sera mis à jour après calcul
                'adresse_livraison' => $request->adresse_livraison,
            ]);

            foreach ($panierItems as $item) {
                $prixLigne = $item->quantite * $item->produit->prix;
                $total += $prixLigne;

                // Création de l'item de commande
                CommandeItem::create([
                    'commande_id' => $commande->id,
                    'produit_id'  => $item->produit_id,
                    'quantite'    => $item->quantite,
                    'prix'        => $item->produit->prix,
                ]);

                // Décrémentation du stock
                $item->produit->decrement('stock', $item->quantite);

                // Suppression de l'article du panier
                $item->delete();
            }

            // Mise à jour du total final
            $commande->update(['total' => $total]);

            // Notification au vendeur de la boutique
            $vendeur_id = Boutique::find($boutique_id)->user_id;
            NotificationService::nouvelleCommande($vendeur_id, $commande);

            return response()->json([
                'success' => true,
                'message' => 'Commande passée avec succès',
                'data'    => $commande->load('commandeItems.produit')
            ], 201);
        });
    }

    #[OA\Get(
        path: '/api/commandes/mes-commandes',
        summary: 'Mes commandes (Client)',
        tags: ['Commandes'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste récupérée')
        ]
    )]
    public function mesCommandes()
    {
        $commandes = Commande::where('user_id', auth()->id())
            ->with(['commandeItems.produit', 'boutique'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Liste de vos commandes récupérée avec succès',
            'data'    => $commandes
        ], 200);
    }

    #[OA\Get(
        path: '/api/commandes/{id}',
        summary: 'Détails d\'une commande',
        tags: ['Commandes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails récupérés'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Non trouvée')
        ]
    )]
    public function show($id)
    {
        $commande = Commande::with(['commandeItems.produit', 'boutique', 'paiement', 'user'])->find($id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        $user = auth()->user();

        // Vérification des droits : le client ou le vendeur de la boutique concernée
        $isClient = $commande->user_id === $user->id;
        $isVendeur = $commande->boutique->user_id === $user->id;

        if (!$isClient && !$isVendeur) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette commande',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails de la commande récupérés',
            'data'    => $commande
        ], 200);
    }

    #[OA\Get(
        path: '/api/commandes/boutique',
        summary: 'Commandes reçues (Vendeur)',
        tags: ['Commandes'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste récupérée')
        ]
    )]
    public function commandesBoutique()
    {
        $vendeur_id = auth()->id();
        
        // Récupère les IDs des boutiques du vendeur
        $boutiqueIds = Boutique::where('user_id', $vendeur_id)->pluck('id');

        $commandes = Commande::whereIn('boutique_id', $boutiqueIds)
            ->with(['commandeItems.produit', 'user'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Commandes reçues récupérées avec succès',
            'data'    => $commandes
        ], 200);
    }

    #[OA\Put(
        path: '/api/commandes/{id}/statut',
        summary: 'Modifier statut (Vendeur)',
        tags: ['Commandes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['statut'],
                properties: [
                    new OA\Property(property: 'statut', type: 'string', enum: ['confirmee', 'en_livraison', 'livree', 'annulee'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Statut mis à jour')
        ]
    )]
    public function updateStatut(Request $request, $id)
    {
        $commande = Commande::with('boutique')->find($id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        // Vérification que le vendeur possède la boutique
        if ($commande->boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:confirmee,en_livraison,livree,annulee',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Si la commande est annulée par le vendeur, on restitue le stock
        if ($request->statut === 'annulee' && $commande->statut !== 'annulee') {
            foreach ($commande->commandeItems as $item) {
                $item->produit->increment('stock', $item->quantite);
            }
        }

        $commande->update(['statut' => $request->statut]);

        // Notification au client du changement de statut
        NotificationService::statutCommande($commande->user_id, $commande);

        return response()->json([
            'success' => true,
            'message' => 'Statut de la commande mis à jour',
            'data'    => $commande
        ], 200);
    }

    #[OA\Put(
        path: '/api/commandes/{id}/annuler',
        summary: 'Annuler commande (Client)',
        tags: ['Commandes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Commande annulée'),
            new OA\Response(response: 400, description: 'Déjà traitée')
        ]
    )]
    public function annuler($id)
    {
        $commande = Commande::where('user_id', auth()->id())->find($id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        // Seule une commande 'en_attente' peut être annulée par le client
        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler une commande déjà traitée',
            ], 400);
        }

        // Restitution du stock
        foreach ($commande->commandeItems as $item) {
            $item->produit->increment('stock', $item->quantite);
        }

        $commande->update(['statut' => 'annulee']);

        return response()->json([
            'success' => true,
            'message' => 'Commande annulée avec succès',
            'data'    => $commande
        ], 200);
    }
}
