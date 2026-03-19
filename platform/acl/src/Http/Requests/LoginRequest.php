<?php

namespace Alphasky\ACL\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class LoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:120'],
            'password' => ['required', 'string', 'min:6', 'max:120'],
        ];
    }
}
