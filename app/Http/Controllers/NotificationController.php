<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use UsesTpqScope;

    public function index(Request $request)
    {
        $query = Notification::where('tpq_id', $this->currentTpqId())
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            });

        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->where('is_read', false);
            }

            if ($request->status === 'read') {
                $query->where('is_read', true);
            }
        }

        return response()->json([
            'unread_count' => Notification::where('tpq_id', $this->currentTpqId())
                ->where(function ($q) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', auth()->id());
                })
                ->where('is_read', false)
                ->count(),

            'data' => $query->latest()->get(),
        ]);
    }

    public function readAll()
    {
        Notification::where('tpq_id', $this->currentTpqId())
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            })
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);

        return response()->json([
            'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca.',
        ]);
    }

    public function destroy(string $id)
    {
        $notification = Notification::where('tpq_id', $this->currentTpqId())
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            })
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notifikasi berhasil dihapus.',
        ]);
    }

    public function destroyAll()
    {
        Notification::where('tpq_id', $this->currentTpqId())
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            })
            ->delete();

        return response()->json([
            'message' => 'Semua notifikasi berhasil dihapus.',
        ]);
    }
}
