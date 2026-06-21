<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HasuraActionController extends Controller
{
    public function login(Request $request)
    {
        $input = $request->input('input');

        $user = User::where('email', $input['email'])->first();

        if (!$user || !Hash::check($input['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 400);
        }

        $token = $user->createToken('hasura-action')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function register(Request $request)
    {
        $input = $request->input('input');

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $token = $user->createToken('hasura-action')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function validateUser(Request $request)
    {
        $input = $request->input('input');

        $user = User::find($input['user_id']);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 400);
        }

        return response()->json([
            'valid' => true,
            'user_id' => $user->id,
            'name' => $user->name,
        ]);
    }
}
