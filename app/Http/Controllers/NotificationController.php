<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $notifications = $request->user()->unreadNotifications()->latest()->limit(5)->get();
            return response()->json([
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'items'        => $notifications,
            ]);
        }

        $notifications = $request->user()->notifications()->paginate(20);
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->through(fn ($n) => [
                'id'         => $n->id,
                'type'       => class_basename($n->type),
                'data'       => $n->data,
                'read_at'    => $n->read_at?->diffForHumans(),
                'is_unread'  => is_null($n->read_at),
                'created_at' => $n->created_at->diffForHumans(),
                'read_url'   => route('notifications.read', $n->id),
                'destroy_url'=> route('notifications.destroy', $n->id),
            ]),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        abort_unless($notification->notifiable_id === $request->user()->id, 403);
        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        $this->notificationService->markAllRead($request->user());
        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        abort_unless($notification->notifiable_id === $request->user()->id, 403);
        $notification->delete();

        return response()->json(['success' => true]);
    }
}
