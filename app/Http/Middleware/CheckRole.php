<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $account = $request->user();

        if (!$account || !$account->roles->contains('name', $role)) {
            return ApiResponse::error('Unauthorized',403);
        }

        return $next($request);
    }
}
