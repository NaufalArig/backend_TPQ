<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\Notification;

class NotificationController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        $notifications = Notification::with([
            'student:id,tpq_id,name,birth_date,join_date,status',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $this->currentTpqId())
            ->latest()
            ->get();

        $unread = Notification::where('tpq_id', $this->currentTpqId())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'data' => $notifications,
            'unread' => $unread,
        ]);
    }

    public function markAsRead(string $id)
    {
        $notification = Notification::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $notification->update([
            'is_read' => true,
        ]);

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification->fresh([
                'student:id,tpq_id,name,birth_date,join_date,status',
                'user:id,tpq_id,name,username',
            ]),
        ]);
    }

    public function markAllAsRead()
    {
        Notification::where('tpq_id', $this->currentTpqId())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
