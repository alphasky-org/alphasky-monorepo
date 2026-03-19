<?php

namespace Alphasky\ACL\Http\Requests;

use Alphasky\ACL\Models\User;
use Alphasky\Support\Http\Requests\Request;

class UpdatePasswordRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'password' => 'required|string|min:6|max:60|confirmed',
        ];

        $user = $this->route('user');

        if ($user instanceof User && $user->exists && $user->is($this->user())) {
            $rules['old_password'] = 'required|string|min:6|max:60|current_password';
        }

        return $rules;
    }
}
