<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion des produits
 */
class ProduitController extends Controller
{
    #[OA\Get(
        path: '/api/produits',
        summary: 'Liste des produits actifs',
        tags: ['Produits'],
        parameters: [
            new OA\Parameter(name: 'boutique_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'categorie_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des produits récupérée')
        ]
    )]
    public function index(Request $request)
    {
        $query = Produit::where('statut', 'actif');

        // Filtrage par boutique
        if ($request->has('boutique_id')) {
            $query->where('boutique_id', $request->boutique_id);
        }

        // Filtrage par catégorie
        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        $produits = $query->paginate(12);

        return response()->json([
            'success' => true,
            'message' => 'Liste des produits récupérée avec succès',
            'data'    => $produits
        ], 200);
    }

    #[OA\Post(
        path: '/api/produits',
        summary: 'Création d\'un produit',
        tags: ['Produits'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['boutique_id', 'categorie_id', 'nom', 'prix', 'stock'],
                    properties: [
                        new OA\Property(property: 'boutique_id', type: 'integer'),
                        new OA\Property(property: 'categorie_id', type: 'integer'),
                        new OA\Property(property: 'nom', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'prix', type: 'number'),
                        new OA\Property(property: 'stock', type: 'integer'),
                        new OA\Property(property: 'images[]', type: 'array', items: new OA\Items(type: 'string', format: 'binary'))
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Produit créé'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'boutique_id'  => 'required|exists:boutiques,id',
            'categorie_id' => 'required|exists:categories,id',
            'nom'          => 'required|string|max:200',
            'description'  => 'nullable|string',
            'prix'         => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'images'       => 'nullable|array',
            'images.*'     => 'image|max:2048',
            'statut'       => 'nullable|in:actif,inactif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Vérification de la possession de la boutique
        $boutique = Boutique::find($request->boutique_id);
        if ($boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à ajouter un produit à cette boutique',
            ], 403);
        }

        // Traitement des images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('produits', 'public');
            }
        }

        // Création du produit
        $produit = Produit::create([
            'boutique_id'  => $request->boutique_id,
            'categorie_id' => $request->categorie_id,
            'nom'          => $request->nom,
            'description'  => $request->description,
            'prix'         => $request->prix,
            'stock'        => $request->stock,
            'images'       => $imagePaths,
            'statut'       => $request->statut ?? 'actif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data'    => $produit
        ], 201);
    }

    #[OA\Get(
        path: '/api/produits/{id}',
        summary: 'Détails d\'un produit',
        tags: ['Produits'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails récupérés'),
            new OA\Response(response: 404, description: 'Produit non trouvé')
        ]
    )]
    public function show($id)
    {
        $produit = Produit::with(['boutique', 'categorie', 'avis.user'])->find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails du produit récupérés avec succès',
            'data'    => $produit
        ], 200);
    }

    #[OA\Put(
        path: '/api/produits/{id}',
        summary: 'Mise à jour d\'un produit',
        tags: ['Produits'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom', type: 'string'),
                    new OA\Property(property: 'prix', type: 'number'),
                    new OA\Property(property: 'stock', type: 'integer')
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
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        // Vérification de la possession (via la boutique du produit)
        $boutique = Boutique::find($produit->boutique_id);
        if ($boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'boutique_id'  => 'nullable|exists:boutiques,id',
            'categorie_id' => 'nullable|exists:categories,id',
            'nom'          => 'nullable|string|max:200',
            'description'  => 'nullable|string',
            'prix'         => 'nullable|numeric|min:0',
            'stock'        => 'nullable|integer|min:0',
            'images'       => 'nullable|array',
            'images.*'     => 'image|max:2048',
            'statut'       => 'nullable|in:actif,inactif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Mise à jour des champs
        $produit->fill($request->only([
            'boutique_id', 'categorie_id', 'nom', 'description', 'prix', 'stock', 'statut'
        ]));

        // Gestion des images (remplacement des anciennes si de nouvelles sont envoyées)
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('produits', 'public');
            }
            $produit->images = $imagePaths;
        }

        $produit->save();

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès',
            'data'    => $produit
        ], 200);
    }

    #[OA\Delete(
        path: '/api/produits/{id}',
        summary: 'Suppression d\'un produit',
        tags: ['Produits'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Produit supprimé'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function destroy($id)
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        $boutique = Boutique::find($produit->boutique_id);
        if ($boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $produit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès',
        ], 200);
    }

    #[OA\Get(
        path: '/api/produits/mes-produits',
        summary: 'Liste de mes produits (Vendeur)',
        tags: ['Produits'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste récupérée')
        ]
    )]
    public function mesProduits()
    {
        // Récupère les IDs des boutiques appartenant au vendeur
        $boutiqueIds = Boutique::where('user_id', auth()->id())->pluck('id');

        $produits = Produit::whereIn('boutique_id', $boutiqueIds)
            ->with(['boutique', 'categorie'])
            ->paginate(12);

        return response()->json([
            'success' => true,
            'message' => 'Vos produits ont été récupérés avec succès',
            'data'    => $produits
        ], 200);
    }
}
