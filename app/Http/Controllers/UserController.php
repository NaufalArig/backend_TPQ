<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::select('id','name','email','role')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,guru,bendahara',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'role' => 'required|in:admin,guru,bendahara',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->update($request->only(['name','email','role']));

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun sendiri'
            ], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
