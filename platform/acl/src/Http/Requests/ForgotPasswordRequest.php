<?php

namespace Alphasky\ACL\Http\Requests;

use Alphasky\Base\Rules\EmailRule;
use Alphasky\Support\Http\Requests\Request;

class ForgotPasswordRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', new EmailRule()],
        ];
    }
}
