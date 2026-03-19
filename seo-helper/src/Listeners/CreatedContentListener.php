<?php

namespace Alphasky\SeoHelper\Listeners;

use Alphasky\Base\Events\CreatedContentEvent;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\SeoHelper\Facades\SeoHelper;
use Exception;

class CreatedContentListener
{
    public function handle(CreatedContentEvent $event): void
    {
        try {
            SeoHelper::saveMetaData($event->screen, $event->request, $event->data);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
