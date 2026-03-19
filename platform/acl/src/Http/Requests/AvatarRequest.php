<?php

namespace Alphasky\ACL\Http\Requests;

use Alphasky\Media\Facades\RvMedia;
use Alphasky\Support\Http\Requests\Request;

class AvatarRequest extends Request
{
    public function rules(): array
    {
        return [
            'avatar_file' => RvMedia::imageValidationRule(),
        ];
    }
}
