<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    use RedirectsAfterAuth;

    public function toResponse($request)
    {
        return $this->redirectAfterAuth($request)->with('tracking_events', ['sign_up']);
    }
}
