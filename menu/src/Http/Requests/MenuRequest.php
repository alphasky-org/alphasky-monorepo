<?php

namespace Alphasky\Menu\Http\Requests;

use Alphasky\Base\Enums\BaseStatusEnum;
use Alphasky\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class MenuRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'deleted_nodes' => ['nullable', 'string'],
            'menu_nodes' => ['nullable', 'string'],
            'status' => Rule::in(BaseStatusEnum::values()),
        ];
    }
}
