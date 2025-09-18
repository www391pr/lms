<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $rolesArray = explode('|', $roles);
        foreach ($rolesArray as $role) {
            $role = trim($role);

            if ($role === 'student' && $request->user()->isStudent()) {
                return $next($request);
            }

            if ($role === 'instructor' && $request->user()->isInstructor()) {
                if (!$request->user()->instructor->verified) {
                    // Allow only CV upload route
                    if ($request->is('api/instructor/upload-cv')) {
                        return $next($request);
                    }

                    return response()->json(['message' => 'Your account is not verified. You can only upload your CV.'], 403);
                }

                return $next($request);
            }

            if ($role === 'admin' && $request->user()->isAdmin()) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Access denied. Insufficient role.'], 403);
    }
}
