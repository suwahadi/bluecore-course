<?php

namespace App\Http\Responses;

use App\Support\NextPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RedirectsAfterAuth
{
    protected function redirectAfterAuth(Request $request): RedirectResponse
    {
        $next = NextPath::sanitize($request->input('next'));

        if ($next !== null) {
            $request->session()->forget('url.intended');

            return redirect()->to($next);
        }

        return redirect()->intended($request->user()?->isAdmin() ? '/admin' : '/');
    }
}
