<?php

namespace Alphasky\Base\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class SelectSearchAjaxRequest extends Request
{
    public function rules(): array
    {
        return [
            'search' => ['required', 'string'],
            'page' => ['required', 'integer'],
        ];
    }
}
