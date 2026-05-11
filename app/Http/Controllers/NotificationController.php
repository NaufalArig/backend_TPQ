<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(
            Notification::with('santri')
                ->latest()
                ->get()
        );
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);

        $notification->update([
            'dibaca' => true
        ]);

        return response()->json([
            'message' => 'Notifikasi ditandai telah dibaca'
        ]);
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(Notification $notification) {}

    public function edit(Notification $notification) {}

    public function update(Request $request, Notification $notification) {}

    public function destroy(Notification $notification) {}
}
