<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * POST /api/notifications/token
     * Sauvegarde le token FCM de l'utilisateur connecté.
     */
    public function saveToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|min:10',
        ]);

        $user = Auth::user();
        $user->updateFcmToken($request->fcm_token);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM enregistré avec succès.',
        ]);
    }

    /**
     * DELETE /api/notifications/token
     * Supprime le token FCM (à la déconnexion).
     */
    public function removeToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        $user->clearFcmToken();

        return response()->json([
            'success' => true,
            'message' => 'Token FCM supprimé.',
        ]);
    }

    /**
     * GET /api/notifications/history
     * Retourne l'historique des notifications de l'utilisateur connecté.
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = NotificationLog::forUser($user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'body'       => $n->body,
                'type'       => $n->type,
                'data'       => $n->data,
                'created_at' => $n->created_at?->toIso8601String(),
                'read_at'    => $n->read_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $notifications,
            'unread'  => NotificationLog::forUser($user->id)->unread()->count(),
        ]);
    }

    /**
     * POST /api/notifications/mark-read/{id}
     * Marque une notification comme lue.
     */
    public function markRead(int $id): JsonResponse
    {
        $user = Auth::user();

        $notification = NotificationLog::forUser($user->id)->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue.',
        ]);
    }

    /**
     * POST /api/notifications/mark-all-read
     * Marque toutes les notifications comme lues.
     */
    public function markAllRead(): JsonResponse
    {
        $user = Auth::user();

        NotificationLog::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues.',
        ]);
    }
}
