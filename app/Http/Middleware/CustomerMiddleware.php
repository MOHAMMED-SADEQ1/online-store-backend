<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'customer') {
            return response()->json([
                'message' => 'Unauthorized. Customer access only.',
                'code'    => 403,
            ], 403);
        }

        return $next($request);
    }
}
