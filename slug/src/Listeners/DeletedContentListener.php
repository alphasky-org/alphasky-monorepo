<?php

namespace Alphasky\Slug\Listeners;

use Alphasky\Base\Contracts\BaseModel;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Slug\Facades\SlugHelper;
use Alphasky\Slug\Models\Slug;

class DeletedContentListener
{
    public function handle(DeletedContentEvent $event): void
    {
        if ($event->data instanceof BaseModel && SlugHelper::isSupportedModel($event->data::class)) {
            Slug::query()->where([
                'reference_id' => $event->data->getKey(),
                'reference_type' => $event->data::class,
            ])->delete();
        }
    }
}
