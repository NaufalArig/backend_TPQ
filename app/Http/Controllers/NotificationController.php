<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Santri;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index()
    {
        $santriPending = Santri::where('status', 'pending')
            ->where('notifikasi_usia', false)
            ->get();

        foreach ($santriPending as $santri) {
            $umur = Carbon::parse($santri->tanggal_lahir)->age;

            if ($umur >= 3) {
                Notification::firstOrCreate(
                    ['santri_id' => $santri->id],
                    [
                        'judul' => 'Santri Siap Aktif',
                        'pesan' => "{$santri->nama} sudah berusia {$umur} tahun dan siap diaktifkan.",
                        'dibaca' => false,
                    ]
                );

                $santri->update(['notifikasi_usia' => true]);
            }
        }

        $notifications = Notification::with('santri:id,nama')
            ->orderBy('dibaca', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data'   => $notifications,
            'unread' => $notifications->where('dibaca', false)->count(),
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['dibaca' => true]);

        return response()->json(['message' => 'Notifikasi ditandai sebagai dibaca.']);
    }

    public function markAllAsRead()
    {
        Notification::where('dibaca', false)->update(['dibaca' => true]);

        return response()->json(['message' => 'Semua notifikasi telah dibaca.']);
    }
}
