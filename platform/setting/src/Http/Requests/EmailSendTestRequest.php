<?php

namespace Alphasky\Setting\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class EmailSendTestRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
