<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion des boutiques
 */
class BoutiqueController extends Controller
{
    #[OA\Get(
        path: '/api/boutiques',
        summary: 'Liste des boutiques actives',
        tags: ['Boutiques'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des boutiques récupérée')
        ]
    )]
    public function index()
    {
        // Récupère les boutiques actives avec le nombre de produits
        $boutiques = Boutique::where('statut', 'actif')
            ->withCount('produits')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Liste des boutiques récupérée avec succès',
            'data'    => $boutiques
        ], 200);
    }

    #[OA\Post(
        path: '/api/boutiques',
        summary: 'Création d\'une boutique',
        tags: ['Boutiques'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'nom', type: 'string', example: 'Ma Boutique'),
                        new OA\Property(property: 'description', type: 'string', example: 'Description...'),
                        new OA\Property(property: 'logo', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Boutique créée'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom'         => 'required|string|max:150|unique:boutiques',
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Traitement du logo si présent
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('boutiques/logos', 'public');
        }

        // Création de la boutique
        $boutique = Boutique::create([
            'user_id'     => auth()->id(),
            'nom'         => $request->nom,
            'description' => $request->description,
            'logo'        => $logoPath,
            'statut'      => 'inactif', // Statut par défaut
            'slug'        => Str::slug($request->nom),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Boutique créée avec succès. En attente d\'activation.',
            'data'    => $boutique
        ], 201);
    }

    #[OA\Get(
        path: '/api/boutiques/{id}',
        summary: 'Détails d\'une boutique',
        tags: ['Boutiques'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails récupérés'),
            new OA\Response(response: 404, description: 'Boutique non trouvée')
        ]
    )]
    public function show($id)
    {
        $boutique = Boutique::with('produits')->find($id);

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails de la boutique récupérés avec succès',
            'data'    => $boutique
        ], 200);
    }

    #[OA\Put(
        path: '/api/boutiques/{id}',
        summary: 'Mise à jour d\'une boutique',
        tags: ['Boutiques'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom', type: 'string'),
                    new OA\Property(property: 'description', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mise à jour réussie'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Non trouvée')
        ]
    )]
    public function update(Request $request, $id)
    {
        $boutique = Boutique::find($id);

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non trouvée',
            ], 404);
        }

        // Vérification de la possession
        if ($boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette boutique',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'nom'         => 'nullable|string|max:150|unique:boutiques,nom,' . $id,
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Mise à jour des champs
        if ($request->has('nom')) {
            $boutique->nom = $request->nom;
            $boutique->slug = Str::slug($request->nom);
        }

        if ($request->has('description')) {
            $boutique->description = $request->description;
        }

        // Gestion du logo
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('boutiques/logos', 'public');
            $boutique->logo = $logoPath;
        }

        $boutique->save();

        return response()->json([
            'success' => true,
            'message' => 'Boutique mise à jour avec succès',
            'data'    => $boutique
        ], 200);
    }

    #[OA\Put(
        path: '/api/boutiques/{id}/statut',
        summary: 'Bascule le statut (Admin)',
        tags: ['Boutiques'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statut mis à jour'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function toggleStatut($id)
    {
        $boutique = Boutique::find($id);

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non trouvée',
            ], 404);
        }

        // Bascule entre actif et inactif
        $boutique->statut = ($boutique->statut === 'actif') ? 'inactif' : 'actif';
        $boutique->save();

        // Si la boutique est activée, on notifie le vendeur
        if ($boutique->statut === 'actif') {
            NotificationService::boutiqueActivee($boutique->user_id, $boutique);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut de la boutique mis à jour avec succès',
            'data'    => [
                'id'     => $boutique->id,
                'statut' => $boutique->statut
            ]
        ], 200);
    }
}
