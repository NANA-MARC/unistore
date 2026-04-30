<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur pour la gestion des catégories de produits
 */
class CategorieController extends Controller
{
    /**
     * Liste toutes les catégories d'une boutique spécifique
     */
    public function index($boutique_id)
    {
        $categories = Categorie::where('boutique_id', $boutique_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des catégories récupérée avec succès',
            'data'    => $categories
        ], 200);
    }

    /**
     * Création d'une nouvelle catégorie pour une boutique
     */
    public function store(Request $request, $boutique_id)
    {
        $boutique = Boutique::find($boutique_id);

        if (!$boutique) {
            return response()->json([
                'success' => false,
                'message' => 'Boutique non trouvée',
            ], 404);
        }

        // Vérification de la possession de la boutique
        if ($boutique->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à ajouter une catégorie à cette boutique',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'nom'   => 'required|string|max:150',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Traitement de l'image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        // Création
        $categorie = Categorie::create([
            'boutique_id' => $boutique_id,
            'nom'         => $request->nom,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data'    => $categorie
        ], 201);
    }

    /**
     * Mise à jour d'une catégorie
     */
    public function update(Request $request, $boutique_id, $id)
    {
        $boutique = Boutique::find($boutique_id);
        $categorie = Categorie::find($id);

        if (!$boutique || !$categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Ressource non trouvée',
            ], 404);
        }

        // Vérification de possession et lien boutique-catégorie
        if ($boutique->user_id !== auth()->id() || $categorie->boutique_id != $boutique_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'nom'   => 'nullable|string|max:150',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Mise à jour
        if ($request->has('nom')) {
            $categorie->nom = $request->nom;
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $categorie->image = $imagePath;
        }

        $categorie->save();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès',
            'data'    => $categorie
        ], 200);
    }

    /**
     * Suppression d'une catégorie
     */
    public function destroy($boutique_id, $id)
    {
        $boutique = Boutique::find($boutique_id);
        $categorie = Categorie::find($id);

        if (!$boutique || !$categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Ressource non trouvée',
            ], 404);
        }

        if ($boutique->user_id !== auth()->id() || $categorie->boutique_id != $boutique_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $categorie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès',
        ], 200);
    }
}
