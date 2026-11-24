<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScalarAuthInput
{
    private const FIELDS = ['name', 'email', 'phone', 'password', 'password_confirmation', 'current_password', 'token', 'next', 'remember'];

    public function handle(Request $request, Closure $next): Response
    {
        $arrays = array_filter($request->only(self::FIELDS), 'is_array');

        if ($arrays !== []) {
            $request->merge(array_fill_keys(array_keys($arrays), null));
        }

        return $next($request);
    }
}
