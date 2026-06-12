<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogService;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'username' => 'required|string|max:255|unique:users,username',
    //         'email' => 'nullable|email|max:255|unique:users,email',
    //         'password' => 'required|string|min:6',
    //         'role' => 'nullable|in:admin,teacher,treasurer',
    //         'status' => 'nullable|in:active,inactive',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validasi gagal',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     $user = User::create([
    //         'name' => $request->name,
    //         'username' => $request->username,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //         'role' => $request->role ?? 'teacher',
    //         'status' => $request->status ?? 'active',
    //     ]);

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Register berhasil',
    //         'token' => $token,
    //         'user' => $this->userPayload($user),
    //     ], 201);
    // }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Username atau password salah',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Akun tidak aktif',
            ], 403);
        }

        if (!$user->tpq_id) {
            return response()->json([
                'status' => false,
                'message' => 'Akun belum terhubung dengan TPQ',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLogService::log(
            action: 'login',
            module: 'auth',
            entity: $user,
            oldValues: null,
            newValues: [
                'tpq_id' => $user->tpq_id,
                'username' => $user->username,
                'role' => $user->role,
                'status' => $user->status,
            ],
            description: 'User logged in: ' . $user->username,
            userId: $user->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 200);
    }

    public function user(Request $request)
    {
        return response()->json(
            $this->userPayload($request->user())
        );
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $oldValues = $user->toArray();

        $validated = $request->validate([
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
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $validated['photo'] = $request->file('photo')
                ->store('users/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        $user->update($validated);

        ActivityLogService::log(
            action: 'update',
            module: 'profile',
            entity: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray(),
            description: 'Updated profile: ' . $user->username,
            userId: $user->id
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password lama tidak sesuai',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLogService::log(
            action: 'update',
            module: 'profile',
            entity: $user,
            oldValues: null,
            newValues: ['password_changed' => true],
            description: 'Changed profile password: ' . $user->username,
            userId: $user->id
        );

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('tpq:id,name');

        return [
            'id' => $user->id,
            'tpq_id' => $user->tpq_id,
            'tpq' => $user->tpq ? [
                'id' => $user->tpq->id,
                'name' => $user->tpq->name,
            ] : null,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'photo' => $user->photo,
        ];
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        ActivityLogService::log(
            action: 'logout',
            module: 'auth',
            entity: $user,
            oldValues: null,
            newValues: [
                'tpq_id' => $user->tpq_id,
                'username' => $user->username,
                'role' => $user->role,
            ],
            description: 'User logged out: ' . $user->username,
            userId: $user->id
        );

        $user->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}
