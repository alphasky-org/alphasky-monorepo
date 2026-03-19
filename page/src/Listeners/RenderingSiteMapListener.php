<?php

namespace Alphasky\Page\Listeners;

use Alphasky\Base\Supports\RepositoryHelper;
use Alphasky\Page\Models\Page;
use Alphasky\Theme\Events\RenderingSiteMapEvent;
use Alphasky\Theme\Facades\SiteMapManager;

class RenderingSiteMapListener
{
    public function handle(RenderingSiteMapEvent $event): void
    {
        if ($event->key == 'pages') {
            $pages = Page::query()
                ->wherePublished()->latest()
                ->select(['id', 'name', 'updated_at'])
                ->with('slugable');

            $pages = RepositoryHelper::applyBeforeExecuteQuery($pages, new Page())->get();

            foreach ($pages as $page) {
                SiteMapManager::add($page->url, $page->updated_at, '0.8');
            }
        }
    }
}
