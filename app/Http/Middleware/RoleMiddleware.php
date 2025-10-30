<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Authentification requise', 401, 'UNAUTHENTICATED');
        }

        // Déterminer le rôle de l'utilisateur
        $userRole = $user->userable_type === 'App\\Models\\Admin' ? 'admin' : 'client';

        // Vérifier si l'utilisateur a le rôle requis
        if ($userRole !== $role) {
            return $this->errorResponse('Permissions insuffisantes', 403, 'INSUFFICIENT_PERMISSIONS');
        }

        return $next($request);
    }
}