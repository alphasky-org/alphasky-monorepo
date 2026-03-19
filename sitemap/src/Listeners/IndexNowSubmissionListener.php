<?php

namespace Alphasky\Sitemap\Listeners;

use Alphasky\Sitemap\Events\SitemapUpdatedEvent;
use Alphasky\Sitemap\Jobs\IndexNowSubmissionJob;
use Alphasky\Sitemap\Services\IndexNowService;
use Carbon\Carbon;

class IndexNowSubmissionListener
{
    public function __construct(protected IndexNowService $indexNowService)
    {
    }

    public function handle(SitemapUpdatedEvent $event): void
    {
        if (! $this->indexNowService->isEnabled()) {
            return;
        }

        if (! $this->indexNowService->getApiKey()) {
            return;
        }

        IndexNowSubmissionJob::dispatch($event->sitemapUrl)
            ->delay(Carbon::now()->addSeconds(30));
    }
}
