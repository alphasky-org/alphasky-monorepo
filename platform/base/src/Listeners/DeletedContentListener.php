<?php

namespace Alphasky\Base\Listeners;

use Alphasky\Base\Contracts\BaseModel;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Models\MetaBox;
use Exception;

class DeletedContentListener
{
    public function handle(DeletedContentEvent $event): void
    {
        if (! $event->data instanceof BaseModel) {
            return;
        }

        try {
            do_action(BASE_ACTION_AFTER_DELETE_CONTENT, $event->screen, $event->request, $event->data);

            MetaBox::query()->where([
                'reference_id' => $event->data->getKey(),
                'reference_type' => $event->data::class,
            ])->delete();
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
