<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    use RedirectsAfterAuth;

    public function toResponse($request)
    {
        $request->session()->put('tracking_events', ['sign_up']);

        return $this->redirectAfterAuth($request);
    }
}
