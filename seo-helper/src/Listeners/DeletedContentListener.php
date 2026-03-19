<?php

namespace Alphasky\SeoHelper\Listeners;

use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\SeoHelper\Facades\SeoHelper;
use Exception;

class DeletedContentListener
{
    public function handle(DeletedContentEvent $event): void
    {
        try {
            SeoHelper::deleteMetaData($event->screen, $event->data);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
