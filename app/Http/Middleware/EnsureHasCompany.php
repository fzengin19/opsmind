<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasCompany
{
    /**
     * Handle an incoming request.
     *
     * Redirect users without a company to the onboarding page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->hasCompany()) {
            return redirect()->route('onboarding.create-company');
        }

        return $next($request);
    }
}
