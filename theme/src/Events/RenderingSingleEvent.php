<?php

namespace Alphasky\Theme\Events;

use Alphasky\Base\Events\Event;
use Alphasky\Slug\Models\Slug;
use Illuminate\Queue\SerializesModels;

class RenderingSingleEvent extends Event
{
    use SerializesModels;

    public function __construct(public Slug $slug)
    {
    }
}
