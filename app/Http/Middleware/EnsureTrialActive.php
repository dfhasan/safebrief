<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrialActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if (! $company || ! $company->trialActive()) {
            return redirect()->route('trial.expired');
        }

        return $next($request);
    }
}
