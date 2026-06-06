<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(
            Notification::with([
                'student:id,name,birth_date,join_date,status',
                'user:id,name,username',
            ])
                ->latest()
                ->get()
        );
    }

    public function markAsRead(string $id)
    {
        $notification = Notification::findOrFail($id);

        $notification->update([
            'is_read' => true,
        ]);

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification,
        ]);
    }

    public function markAllAsRead()
    {
        Notification::where('is_read', false)->update([
            'is_read' => true,
        ]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
