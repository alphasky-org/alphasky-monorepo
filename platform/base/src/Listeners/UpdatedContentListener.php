<?php

namespace Alphasky\Base\Listeners;

use Alphasky\Base\Events\UpdatedContentEvent;
use Alphasky\Base\Facades\BaseHelper;
use Exception;

class UpdatedContentListener
{
    public function handle(UpdatedContentEvent $event): void
    {
        try {
            do_action(BASE_ACTION_AFTER_UPDATE_CONTENT, $event->screen, $event->request, $event->data);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
