<?php

namespace Alphasky\Base\Http\Controllers\Concerns;

use Alphasky\Base\Http\Responses\BaseHttpResponse;

trait HasHttpResponse
{
    public function httpResponse(): BaseHttpResponse
    {
        return BaseHttpResponse::make();
    }
}
