<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    use RedirectsAfterAuth;

    public function toResponse($request)
    {
        return $this->redirectAfterAuth($request);
    }
}
