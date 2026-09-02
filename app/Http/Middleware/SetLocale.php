<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');

        if (is_string($requested) && in_array($requested, ['en', 'bn'], true)) {
            $request->session()->put('locale', $requested);
        }

        app()->setLocale((string) $request->session()->get('locale', config('app.locale')));

        return $next($request);
    }
}
