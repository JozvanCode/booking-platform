<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'users' => [
                'total' => User::count(),
                'customers' => User::where('role', 'customer')->count(),
                'providers' => User::where('role', 'provider')->count(),
                'admins' => User::where('role', 'admin')->count(),
                'blocked' => User::where('is_active', false)->count(),
            ],
            'bookings' => [
                'total' => Booking::count(),
                'pending' => Booking::where('status', 'pending')->count(),
                'confirmed' => Booking::where('status', 'confirmed')->count(),
                'completed' => Booking::where('status', 'completed')->count(),
                'cancelled' => Booking::where('status', 'cancelled')->count(),
            ],
            'services' => [
                'total' => Service::count(),
            ],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['sometimes', Rule::in(['customer', 'provider', 'admin'])],
            'search' => ['sometimes', 'string', 'max:100'],
        ]);

        $users = User::query()
            ->select('id', 'name', 'email', 'role', 'is_active', 'created_at')
            ->when(
                isset($validated['role']),
                fn ($query) => $query->where('role', $validated['role'])
            )
            ->when(
                isset($validated['search']),
                function ($query) use ($validated) {
                    $search = $validated['search'];

                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            )
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    public function showUser(int $id): JsonResponse
    {
        $user = User::query()
            ->select('id', 'name', 'email', 'role', 'is_active', 'created_at')
            ->findOrFail($id);

        return response()->json(['user' => $user]);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['customer', 'provider', 'admin'])],
        ]);

        return DB::transaction(function () use ($request, $id, $validated) {
            // Serialize role changes to protect the last administrator.
            $admins = User::where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $user = User::findOrFail($id);

            if (
                $request->user()->id === $user->id &&
                $validated['role'] !== 'admin'
            ) {
                return response()->json([
                    'message' => 'You cannot remove your own admin role.',
                ], 409);
            }

            if (
                $user->role === 'admin' &&
                $validated['role'] !== 'admin' &&
                $admins->count() <= 1
            ) {
                return response()->json([
                    'message' => 'The last admin cannot be demoted.',
                ], 409);
            }

            $oldRole = $user->role;

            $user->role = $validated['role'];
            $user->save();

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'user.role.updated',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'old_values' => ['role' => $oldRole],
                'new_values' => ['role' => $user->role],
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'User role updated.',
                'user' => $user->only('id', 'name', 'email', 'role'),
            ]);
        });
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $id, $validated) {
            $user = User::lockForUpdate()->findOrFail($id);

            if ($request->user()->id === $user->id && ! $validated['is_active']) {
                return response()->json([
                    'message' => 'You cannot block yourself.',
                ], 409);
            }

            if ($user->role === 'admin' && ! $validated['is_active']) {
                return response()->json([
                    'message' => 'Admin accounts cannot be blocked through this endpoint.',
                ], 409);
            }

            $user->is_active = $validated['is_active'];
            $user->save();

            if (! $user->is_active) {
                $user->tokens()->delete();
            }

            return response()->json([
                'message' => 'User status updated.',
                'user' => $user->only('id', 'role', 'is_active'),
            ]);
        });
    }

    public function bookings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'sometimes',
                Rule::in(['pending', 'confirmed', 'cancelled', 'completed']),
            ],
        ]);

        $bookings = Booking::query()
            ->with('service')
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->latest()
            ->paginate(20);

        return response()->json($bookings);
    }

    public function services(): JsonResponse
    {
        return response()->json(
            Service::query()
                ->with('user:id,name,email')
                ->latest()
                ->paginate(20)
        );
    }

    public function deleteService(int $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        if ($service->bookings()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a service with existing bookings.',
            ], 409);
        }

        $service->delete();

        return response()->json([
            'message' => 'Service deleted.',
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['sometimes', 'string', 'max:100'],
            'actor_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when(
                isset($validated['action']),
                fn ($query) => $query->where('action', $validated['action'])
            )
            ->when(
                isset($validated['actor_id']),
                fn ($query) => $query->where('actor_id', $validated['actor_id'])
            )
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}
