<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Paiement;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion des paiements liés aux commandes
 */
class PaiementController extends Controller
{
    #[OA\Post(
        path: '/api/commandes/{commande_id}/paiement',
        summary: 'Payer une commande',
        tags: ['Paiements'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'commande_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['methode'],
                properties: [
                    new OA\Property(property: 'methode', type: 'string', enum: ['cash', 'mobile_money', 'carte_bancaire'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Paiement initialisé'),
            new OA\Response(response: 400, description: 'Statut invalide ou déjà payé')
        ]
    )]
    public function store(Request $request, $commande_id)
    {
        $commande = Commande::find($commande_id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        // Vérification de la possession
        if ($commande->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette commande',
            ], 403);
        }

        // Vérification du statut de la commande
        if ($commande->statut !== 'confirmee') {
            return response()->json([
                'success' => false,
                'message' => 'La commande doit être confirmée avant le paiement',
            ], 400);
        }

        // Vérification si un paiement validé existe déjà
        $paiementExiste = Paiement::where('commande_id', $commande_id)
            ->where('statut', 'valide')
            ->exists();

        if ($paiementExiste) {
            return response()->json([
                'success' => false,
                'message' => 'Un paiement validé existe déjà pour cette commande',
            ], 400);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'methode' => 'required|in:cash,mobile_money,carte_bancaire',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Création du paiement
        $paiement = Paiement::create([
            'commande_id' => $commande_id,
            'methode'     => $request->methode,
            'statut'      => 'en_attente',
            'montant'     => $commande->total,
            'reference'   => uniqid('PAY-', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement initialisé avec succès',
            'data'    => $paiement
        ], 201);
    }

    #[OA\Put(
        path: '/api/paiements/{id}/valider',
        summary: 'Valider paiement (Vendeur)',
        tags: ['Paiements'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paiement validé'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function valider($id)
    {
        $paiement = Paiement::with('commande.boutique')->find($id);

        if (!$paiement) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé',
            ], 404);
        }

        // Vérification que le vendeur possède la boutique de la commande
        if ($paiement->commande->boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        return DB::transaction(function () use ($paiement) {
            // Mise à jour du paiement
            $paiement->update(['statut' => 'valide']);

            // Mise à jour de la commande
            $paiement->commande->update(['statut' => 'en_livraison']);

            // Notification au client
            NotificationService::paiementValide($paiement->commande->user_id, $paiement);

            return response()->json([
                'success' => true,
                'message' => 'Paiement validé. La commande est maintenant en cours de livraison.',
                'data'    => $paiement
            ], 200);
        });
    }

    #[OA\Put(
        path: '/api/paiements/{id}/rembourser',
        summary: 'Rembourser paiement (Vendeur)',
        tags: ['Paiements'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Remboursé'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function rembourser($id)
    {
        $paiement = Paiement::with(['commande.boutique', 'commande.commandeItems.produit'])->find($id);

        if (!$paiement) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé',
            ], 404);
        }

        // Vérification des droits
        if ($paiement->commande->boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // On ne peut rembourser qu'un paiement valide
        if ($paiement->statut !== 'valide') {
            return response()->json([
                'success' => false,
                'message' => 'Seul un paiement validé peut être remboursé',
            ], 400);
        }

        return DB::transaction(function () use ($paiement) {
            // Mise à jour du paiement
            $paiement->update(['statut' => 'rembourse']);

            // Mise à jour de la commande
            $paiement->commande->update(['statut' => 'annulee']);

            // Restitution du stock
            foreach ($paiement->commande->commandeItems as $item) {
                $item->produit->increment('stock', $item->quantite);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement remboursé et commande annulée. Le stock a été restitué.',
                'data'    => $paiement
            ], 200);
        });
    }

    #[OA\Get(
        path: '/api/commandes/{commande_id}/paiement',
        summary: 'Voir le paiement d\'une commande',
        tags: ['Paiements'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'commande_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paiement trouvé'),
            new OA\Response(response: 404, description: 'Non trouvé')
        ]
    )]
    public function show($commande_id)
    {
        $commande = Commande::with('boutique')->find($commande_id);

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        $user = auth()->user();

        // Vérification des droits (Client de la commande ou Vendeur de la boutique)
        if ($commande->user_id !== $user->id && $commande->boutique->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $paiement = Paiement::where('commande_id', $commande_id)->first();

        if (!$paiement) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun paiement trouvé pour cette commande',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paiement récupéré avec succès',
            'data'    => $paiement
        ], 200);
    }
}
