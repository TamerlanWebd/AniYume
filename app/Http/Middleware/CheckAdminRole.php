<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Forbidden. Admin role required.',
            ], 403);
        }

        return $next($request);
    }
}
