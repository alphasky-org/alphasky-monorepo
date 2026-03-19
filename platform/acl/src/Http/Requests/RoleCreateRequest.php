<?php

namespace Alphasky\ACL\Http\Requests;

use Alphasky\Base\Rules\OnOffRule;
use Alphasky\Support\Http\Requests\Request;

class RoleCreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'min:3'],
            'description' => ['nullable', 'string', 'string', 'max:255'],
            'is_default' => [new OnOffRule()],
        ];
    }
}
