<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active !== true) {
            return response()->json([
                'message' => 'Your account has been disabled.',
            ], 403);
        }

        return $next($request);
    }
}
