<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Jobs\PublishUserEventJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Daftar semua user (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                                                  ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * GET /api/users/{id}
     * Detail satu user
     */
    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * PUT /api/users/{id}
     * Update profil user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|unique:users,email,{$id}",
            'password' => 'sometimes|string|min:8|confirmed',
            'role'     => 'sometimes|in:customer,admin',
        ]);

        $user->update($validated);

        // Publish event update ke RabbitMQ
        PublishUserEventJob::dispatch('user.updated', [
            'user_id' => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
        ]);

        return response()->json([
            'message' => 'User berhasil diupdate',
            'data'    => $user->fresh(),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Nonaktifkan user (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        // Publish event deactivate ke RabbitMQ
        PublishUserEventJob::dispatch('user.deactivated', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return response()->json([
            'message' => 'User berhasil dinonaktifkan',
        ]);
    }

    /**
     * GET /api/users/validate/{id}
     * Endpoint internal: validasi user dari service lain
     */
    public function validateUser(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user || ! $user->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'User tidak ditemukan atau tidak aktif',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'data'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }
}
