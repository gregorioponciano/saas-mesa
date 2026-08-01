<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::with('tenant')->orderBy('created_at', 'desc');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->paginate($request->get('per_page', 20))
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->roleLabel(),
                'role_color' => $user->roleColor(),
                'is_staff' => $user->is_staff,
                'tenant_id' => $user->tenant_id,
                'tenant_name' => $user->tenant?->name,
                'created_at' => $user->created_at,
            ]);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_SUPERADMIN,
            'tenant_id' => null,
            'is_staff' => true,
        ]);

        $this->auditService->log(
            'user.create_superadmin',
            "Superadmin \"{$user->name}\" criado.",
            ['user_id' => $user->id],
            entityType: User::class,
            entityId: (string) $user->id
        );

        return response()->json([
            'message' => 'Superadmin criado com sucesso.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    public function revoke(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Você não pode revogar o próprio acesso.'], 422);
        }

        if (! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Este usuário não é superadmin.'], 422);
        }

        $remainingSuperAdmins = User::where('role', User::ROLE_SUPERADMIN)
            ->where('id', '!=', $user->id)
            ->count();

        if ($remainingSuperAdmins === 0) {
            return response()->json(['error' => 'Não é possível revogar o último superadmin da plataforma.'], 422);
        }

        $user->update([
            'role' => User::ROLE_CLIENTE,
            'is_staff' => false,
            'tenant_id' => null,
        ]);

        $this->auditService->log(
            'user.revoke_superadmin',
            "Acesso de superadmin revogado para \"{$user->name}\".",
            ['user_id' => $user->id],
            entityType: User::class,
            entityId: (string) $user->id
        );

        return response()->json([
            'message' => 'Acesso de superadmin revogado.',
            'user_id' => $user->id,
        ]);
    }
}
