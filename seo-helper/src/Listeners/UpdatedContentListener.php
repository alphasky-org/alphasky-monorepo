<?php

namespace Alphasky\SeoHelper\Listeners;

use Alphasky\Base\Events\UpdatedContentEvent;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\SeoHelper\Facades\SeoHelper;
use Exception;

class UpdatedContentListener
{
    public function handle(UpdatedContentEvent $event): void
    {
        try {
            SeoHelper::saveMetaData($event->screen, $event->request, $event->data);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
