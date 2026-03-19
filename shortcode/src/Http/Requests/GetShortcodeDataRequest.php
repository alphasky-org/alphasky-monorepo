<?php

namespace Alphasky\Shortcode\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class GetShortcodeDataRequest extends Request
{
    public function rules(): array
    {
        return [
            'key' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:1000000'],
        ];
    }
}
