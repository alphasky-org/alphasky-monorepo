<?php

namespace Alphasky\Theme\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class CustomHtmlRequest extends Request
{
    public function rules(): array
    {
        return [
            'custom_header_html' => ['nullable', 'string', 'max:10000'],
            'custom_body_html' => ['nullable', 'string', 'max:10000'],
            'custom_footer_html' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
