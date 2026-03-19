<?php

namespace Alphasky\ACL\Http\Requests;

use Alphasky\Base\Rules\EmailRule;
use Alphasky\Support\Http\Requests\Request;

class ResetPasswordRequest extends Request
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', new EmailRule()],
            'password' => ['required', 'confirmed', 'min:6'],
        ];
    }
}
