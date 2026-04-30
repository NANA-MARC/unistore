<?php

namespace App\Http\Controllers;

use App\Models\Panier;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion du panier utilisateur
 */
class PanierController extends Controller
{
    #[OA\Get(
        path: '/api/panier',
        summary: 'Contenu du panier',
        tags: ['Panier'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Panier récupéré'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function index()
    {
        $user = auth()->user();
        
        // Récupère les articles avec les détails des produits
        $articles = Panier::where('user_id', $user->id)
            ->with(['produit:id,nom,prix,images'])
            ->get();

        // Calcul du total
        $total = 0;
        foreach ($articles as $article) {
            $total += $article->quantite * $article->produit->prix;
        }

        return response()->json([
            'success' => true,
            'message' => 'Panier récupéré avec succès',
            'data'    => [
                'articles' => $articles,
                'total'    => $total
            ]
        ], 200);
    }

    #[OA\Post(
        path: '/api/panier',
        summary: 'Ajouter au panier',
        tags: ['Panier'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['produit_id', 'quantite'],
                properties: [
                    new OA\Property(property: 'produit_id', type: 'integer'),
                    new OA\Property(property: 'quantite', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ajout réussi'),
            new OA\Response(response: 422, description: 'Erreur de validation ou stock')
        ]
    )]
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'quantite'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Vérification du produit (actif et stock)
        $produit = Produit::find($request->produit_id);

        if ($produit->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est pas disponible actuellement',
            ], 422);
        }

        if ($produit->stock < $request->quantite) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour ce produit',
            ], 422);
        }

        $user_id = auth()->id();

        // Vérifie si le produit est déjà dans le panier
        $article = Panier::where('user_id', $user_id)
            ->where('produit_id', $request->produit_id)
            ->first();

        if ($article) {
            // Mise à jour de la quantité
            $nouvelleQuantite = $article->quantite + $request->quantite;
            
            // Vérification du stock pour la nouvelle quantité totale
            if ($produit->stock < $nouvelleQuantite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'ajouter plus d\'articles, stock maximum atteint',
                ], 422);
            }

            $article->update(['quantite' => $nouvelleQuantite]);
            $message = 'Quantité mise à jour dans le panier';
        } else {
            // Création d'un nouvel article
            $article = Panier::create([
                'user_id'    => $user_id,
                'produit_id' => $request->produit_id,
                'quantite'   => $request->quantite,
            ]);
            $message = 'Produit ajouté au panier avec succès';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $article
        ], 201);
    }

    #[OA\Put(
        path: '/api/panier/{id}',
        summary: 'Modifier quantité',
        tags: ['Panier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'quantite', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mise à jour réussie'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function update(Request $request, $id)
    {
        $article = Panier::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier',
            ], 404);
        }

        // Vérification de la possession
        if ($article->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Vérification du stock
        $produit = Produit::find($article->produit_id);
        if ($produit->stock < $request->quantite) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant pour cette quantité',
            ], 422);
        }

        $article->update(['quantite' => $request->quantite]);

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour avec succès',
            'data'    => $article
        ], 200);
    }

    #[OA\Delete(
        path: '/api/panier/{id}',
        summary: 'Supprimer un article',
        tags: ['Panier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Article supprimé'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function destroy($id)
    {
        $article = Panier::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé',
            ], 404);
        }

        if ($article->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier',
        ], 200);
    }

    #[OA\Delete(
        path: '/api/panier',
        summary: 'Vider le panier',
        tags: ['Panier'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Panier vidé')
        ]
    )]
    public function clear()
    {
        Panier::where('user_id', auth()->id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé avec succès',
        ], 200);
    }
}
