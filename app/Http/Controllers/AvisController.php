<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion des avis clients sur les produits
 */
class AvisController extends Controller
{
    #[OA\Get(
        path: '/api/produits/{produit_id}/avis',
        summary: 'Liste des avis d\'un produit',
        tags: ['Avis'],
        parameters: [
            new OA\Parameter(name: 'produit_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Avis récupérés')
        ]
    )]
    public function index($produit_id)
    {
        $avisQuery = Avis::where('produit_id', $produit_id)
            ->with('user:id,name')
            ->latest();

        $avis = $avisQuery->paginate(10);
        
        // Calcul de la note moyenne
        $noteMoyenne = Avis::where('produit_id', $produit_id)->avg('note') ?: 0;

        return response()->json([
            'success' => true,
            'message' => 'Avis récupérés avec succès',
            'data'    => [
                'avis'         => $avis,
                'note_moyenne' => round($noteMoyenne, 1)
            ]
        ], 200);
    }

    #[OA\Post(
        path: '/api/produits/{produit_id}/avis',
        summary: 'Laisser un avis',
        tags: ['Avis'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'produit_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['note'],
                properties: [
                    new OA\Property(property: 'note', type: 'integer', minimum: 1, maximum: 5),
                    new OA\Property(property: 'commentaire', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Avis créé'),
            new OA\Response(response: 403, description: 'Achat non validé')
        ]
    )]
    public function store(Request $request, $produit_id)
    {
        $user_id = auth()->id();

        // Vérification de l'existence du produit
        $produit = Produit::find($produit_id);
        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        // Vérification si le client a déjà laissé un avis
        $dejaDonne = Avis::where('user_id', $user_id)
            ->where('produit_id', $produit_id)
            ->exists();

        if ($dejaDonne) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà laissé un avis sur ce produit',
            ], 400);
        }

        // Vérification si le client a bien acheté et reçu le produit
        $aAchete = Commande::where('user_id', $user_id)
            ->where('statut', 'livree')
            ->whereHas('commandeItems', function ($query) use ($produit_id) {
                $query->where('produit_id', $produit_id);
            })
            ->exists();

        if (!$aAchete) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez laisser un avis que sur un produit que vous avez acheté et reçu',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'note'        => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Création de l'avis
        $avis = Avis::create([
            'user_id'     => $user_id,
            'produit_id'  => $produit_id,
            'note'        => $request->note,
            'commentaire' => $request->commentaire,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avis enregistré avec succès',
            'data'    => $avis
        ], 201);
    }

    #[OA\Put(
        path: '/api/produits/{produit_id}/avis/{id}',
        summary: 'Modifier avis',
        tags: ['Avis'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'produit_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'note', type: 'integer'),
                    new OA\Property(property: 'commentaire', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mis à jour')
        ]
    )]
    public function update(Request $request, $produit_id, $id)
    {
        $avis = Avis::find($id);

        if (!$avis) {
            return response()->json([
                'success' => false,
                'message' => 'Avis non trouvé',
            ], 404);
        }

        // Vérification de l'auteur
        if ($avis->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cet avis',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'note'        => 'nullable|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        $avis->update($request->only(['note', 'commentaire']));

        return response()->json([
            'success' => true,
            'message' => 'Avis mis à jour avec succès',
            'data'    => $avis
        ], 200);
    }

    #[OA\Delete(
        path: '/api/produits/{produit_id}/avis/{id}',
        summary: 'Supprimer avis',
        tags: ['Avis'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'produit_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supprimé')
        ]
    )]
    public function destroy($produit_id, $id)
    {
        $avis = Avis::find($id);

        if (!$avis) {
            return response()->json([
                'success' => false,
                'message' => 'Avis non trouvé',
            ], 404);
        }

        $user = auth()->user();

        // Seul l'auteur ou un super_admin peut supprimer
        if ($avis->user_id !== $user->id && $user->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $avis->delete();

        return response()->json([
            'success' => true,
            'message' => 'Avis supprimé avec succès',
        ], 200);
    }
}
