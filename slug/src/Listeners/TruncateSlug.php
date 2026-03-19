<?php

namespace Alphasky\Slug\Listeners;

use Alphasky\Slug\Models\Slug;

class TruncateSlug
{
    public function handle(): void
    {
        Slug::query()->truncate();
    }
}
