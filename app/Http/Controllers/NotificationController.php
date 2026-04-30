<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Contrôleur pour la gestion des notifications utilisateur
 */
class NotificationController extends Controller
{
    #[OA\Get(
        path: '/api/notifications',
        summary: 'Mes notifications',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste récupérée')
        ]
    )]
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Notifications récupérées avec succès',
            'data'    => $notifications
        ], 200);
    }

    #[OA\Put(
        path: '/api/notifications/{id}/lu',
        summary: 'Marquer comme lu',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Marqué comme lu'),
            new OA\Response(response: 403, description: 'Accès refusé')
        ]
    )]
    public function marquerLu($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $notification->update(['lu' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'data'    => $notification
        ], 200);
    }

    #[OA\Put(
        path: '/api/notifications/tout-lu',
        summary: 'Tout marquer comme lu',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Toutes marquées')
        ]
    )]
    public function marquerToutLu()
    {
        Notification::where('user_id', auth()->id())
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ], 200);
    }

    #[OA\Delete(
        path: '/api/notifications/{id}',
        summary: 'Supprimer une notification',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supprimée')
        ]
    )]
    public function destroy($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée avec succès',
        ], 200);
    }
}
