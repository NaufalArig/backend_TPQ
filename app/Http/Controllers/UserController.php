<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        return response()->json(
            User::select('id', 'tpq_id', 'name', 'username', 'email', 'role', 'status', 'created_at', 'updated_at')
                ->where('tpq_id', $this->currentTpqId())
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,treasurer',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'tpq_id' => $this->currentTpqId(),
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
        ]);

        ActivityLogService::log(
            action: 'create',
            module: 'users',
            entity: $user,
            oldValues: null,
            newValues: $user->toArray(),
            description: 'Created user: ' . $user->name
        );

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function show($id)
    {
        $user = User::select('id', 'tpq_id', 'name', 'username', 'email', 'role', 'status', 'created_at', 'updated_at')
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $oldValues = $user->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',

            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,teacher,treasurer',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'tpq_id' => $this->currentTpqId(),
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        ActivityLogService::log(
            action: 'update',
            module: 'users',
            entity: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray(),
            description: 'Updated user: ' . $user->name
        );

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $user = User::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        if (auth()->id() == $user->id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 400);
        }

        if ($user->role === 'teacher') {
            return response()->json([
                'message' => 'Akun guru tidak bisa dihapus dari menu User. Hapus data guru melalui menu Guru.',
            ], 400);
        }

        $oldValues = $user->toArray();
        $userName = $user->name;

        $user->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'users',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted user: ' . $userName
        );

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
