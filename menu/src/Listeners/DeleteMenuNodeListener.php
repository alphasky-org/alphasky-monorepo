<?php

namespace Alphasky\Menu\Listeners;

use Alphasky\Base\Contracts\BaseModel;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Menu\Facades\Menu;
use Alphasky\Menu\Models\MenuNode;

class DeleteMenuNodeListener
{
    public function handle(DeletedContentEvent $event): void
    {
        if (
            ! $event->data instanceof BaseModel ||
            ! in_array($event->data::class, Menu::getMenuOptionModels())
        ) {
            return;
        }

        MenuNode::query()
            ->where([
                'reference_id' => $event->data->getKey(),
                'reference_type' => $event->data::class,
            ])
            ->delete();
    }
}
