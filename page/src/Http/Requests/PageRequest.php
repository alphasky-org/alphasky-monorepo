<?php

namespace Alphasky\Page\Http\Requests;

use Alphasky\Base\Enums\BaseStatusEnum;
use Alphasky\Base\Rules\MediaImageRule;
use Alphasky\Page\Supports\Template;
use Alphasky\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class PageRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:400'],
            'content' => ['nullable', 'string'],
            'template' => [Rule::in(array_keys(Template::getPageTemplates()))],
            'status' => [Rule::in(BaseStatusEnum::values())],
            'image' => ['nullable', 'string', new MediaImageRule()],
        ];
    }
}
