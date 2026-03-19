<?php

namespace Alphasky\Media\Http\Requests;

use Alphasky\Support\Http\Requests\Request;

class MediaListRequest extends Request
{
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'string'],
        ];
    }
}
