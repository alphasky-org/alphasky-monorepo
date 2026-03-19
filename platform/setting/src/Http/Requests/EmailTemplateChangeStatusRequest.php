<?php

namespace Alphasky\Setting\Http\Requests;

use Alphasky\Base\Rules\OnOffRule;
use Alphasky\Support\Http\Requests\Request;

class EmailTemplateChangeStatusRequest extends Request
{
    public function rules(): array
    {
        return [
            'key' => ['required', 'string'],
            'value' => [new OnOffRule()],
        ];
    }
}
