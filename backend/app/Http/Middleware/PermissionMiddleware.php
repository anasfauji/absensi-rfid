<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions
    ): Response {
        $pengguna = $request->user();

        if (! $pengguna) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $memilikiPermission = collect($permissions)
            ->contains(
                fn ($permission) => $pengguna->hasPermission($permission)
            );

        if (! $memilikiPermission) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        return $next($request);
    }
}