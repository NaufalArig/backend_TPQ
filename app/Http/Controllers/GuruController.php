<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\Guru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogService;

class GuruController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        return response()->json(
            Guru::with('user:id,tpq_id,name,username,email,role,status')
                ->where('tpq_id', $this->currentTpqId())
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // login account
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',

            // teacher data
            'teacher_number' => 'nullable|string|max:255',
            'tpq_number' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'village' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'certificate_from' => 'nullable|string|max:255',
            'certificate_number' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'join_date' => 'required|date',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')
                ->store('teachers/photos', 'public');
        }

        $validated['status'] = Carbon::parse($validated['join_date'])->isFuture()
            ? 'pending'
            : 'active';

        $teacher = DB::transaction(function () use ($validated) {
            $user = User::create([
                'tpq_id' => $this->currentTpqId(),
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'teacher',
                'status' => 'active',
            ]);

            unset($validated['username'], $validated['email'], $validated['password']);

            return Guru::create([
                ...$validated,
                'tpq_id' => $this->currentTpqId(),
                'user_id' => $user->id,
                'age_notification_sent' => false,
            ]);
        });

        ActivityLogService::log(
            action: 'create',
            module: 'teachers',
            entity: $teacher,
            oldValues: null,
            newValues: $teacher->toArray(),
            description: 'Created teacher: ' . $teacher->name
        );

        return response()->json([
            'message' => 'Teacher created successfully',
            'data' => $teacher->load('user:id,tpq_id,name,username,email,role,status'),
        ], 201);
    }

    public function show(string $id)
    {
        $teacher = Guru::with('user:id,tpq_id,name,username,email,role,status')
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        return response()->json($teacher);
    }

    public function update(Request $request, string $id)
    {
        $teacher = Guru::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $oldValues = $teacher->toArray();

        $validated = $request->validate([
            'teacher_number' => 'nullable|string|max:255',
            'tpq_number' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'village' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'certificate_from' => 'nullable|string|max:255',
            'certificate_number' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'join_date' => 'required|date',
            'leave_date' => 'nullable|date',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }

            $validated['photo'] = $request->file('photo')
                ->store('teachers/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        $joinDate = Carbon::parse($validated['join_date']);
        $leaveDate = !empty($validated['leave_date'])
            ? Carbon::parse($validated['leave_date'])
            : null;

        if ($leaveDate && $leaveDate->lte(now())) {
            $validated['status'] = 'inactive';
        } elseif ($joinDate->isFuture()) {
            $validated['status'] = 'pending';
        } else {
            $validated['status'] = 'active';
        }

        DB::transaction(function () use ($teacher, $validated) {
            unset($validated['tpq_id']);

            $teacher->update($validated);

            if ($teacher->user) {
                $teacher->user->update([
                    'tpq_id' => $this->currentTpqId(),
                    'name' => $validated['name'],
                    'status' => $validated['status'] === 'inactive' ? 'inactive' : 'active',
                ]);
            }
        });

        ActivityLogService::log(
            action: 'update',
            module: 'teachers',
            entity: $teacher,
            oldValues: $oldValues,
            newValues: $teacher->fresh()->toArray(),
            description: 'Updated teacher: ' . $teacher->name
        );

        return response()->json([
            'message' => 'Teacher updated successfully',
            'data' => $teacher->fresh('user:id,tpq_id,name,username,email,role,status'),
        ]);
    }

    public function destroy(string $id)
    {
        $teacher = Guru::with('user')
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $oldValues = $teacher->toArray();
        $teacherName = $teacher->name;

        DB::transaction(function () use ($teacher) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }

            $user = $teacher->user;

            $teacher->delete();

            if ($user && $user->role === 'teacher') {
                $user->delete();
            }
        });

        ActivityLogService::log(
            action: 'delete',
            module: 'teachers',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted teacher: ' . $teacherName
        );

        return response()->json([
            'message' => 'Teacher deleted successfully',
        ]);
    }
}
