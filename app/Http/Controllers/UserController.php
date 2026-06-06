<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::select('id', 'name', 'username', 'email', 'role', 'status', 'created_at', 'updated_at')
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
            'role' => 'required|in:admin,teacher,treasurer',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
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
        $user = User::select('id', 'name', 'username', 'email', 'role', 'status', 'created_at', 'updated_at')
            ->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
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
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
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
            'data' => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
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
