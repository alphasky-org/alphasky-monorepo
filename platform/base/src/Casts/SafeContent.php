<?php

namespace Alphasky\Base\Casts;

use Alphasky\Base\Facades\BaseHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SafeContent implements CastsAttributes
{
    public function set($model, string $key, $value, array $attributes)
    {
        return BaseHelper::clean($value);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if (! $value) {
            return $value;
        }

        return html_entity_decode(BaseHelper::clean($value));
    }
}
