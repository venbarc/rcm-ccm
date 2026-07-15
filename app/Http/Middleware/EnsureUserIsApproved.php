<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_approved) {
            return $next($request);
        }

        return redirect()->route('pending-approval');
    }
}
