<?php

namespace Alphasky\Api\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class LoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'account_type' => [config('packages.api.api.account_type_required', false) ? 'required' : 'nullable', 'string', 'in:individual,company'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The email address of the user',
                'example' => 'john.smith@example.com',
            ],
            'password' => [
                'description' => 'The password for the account',
                'example' => 'secure_password',
            ],
            'account_type' => [
                'description' => 'The portal audience selected by the user',
                'example' => 'individual',
            ],
        ];
    }
}
