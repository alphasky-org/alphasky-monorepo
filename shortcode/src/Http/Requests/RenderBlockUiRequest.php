<?php

namespace Alphasky\Shortcode\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class RenderBlockUiRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable', 'string'],
        ];
    }
}
