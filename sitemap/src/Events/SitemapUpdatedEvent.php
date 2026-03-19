<?php

namespace Alphasky\Sitemap\Events;

use Alphasky\Base\Events\Event;

class SitemapUpdatedEvent extends Event
{
    public function __construct(public ?string $sitemapUrl = null)
    {
    }
}
