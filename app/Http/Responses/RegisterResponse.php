<?php

namespace App\Http\Responses;

use App\Support\Tracking;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    use RedirectsAfterAuth;

    public function toResponse($request)
    {
        Tracking::push('sign_up');

        return $this->redirectAfterAuth($request);
    }
}
