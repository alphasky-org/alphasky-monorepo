<?php

namespace Alphasky\Menu\Listeners;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Models\BaseModel;
use Alphasky\Base\Supports\RepositoryHelper;
use Alphasky\Menu\Facades\Menu;
use Alphasky\Menu\Models\MenuNode;
use Alphasky\Slug\Events\UpdatedSlugEvent;
use Exception;

class UpdateMenuNodeUrlListener
{
    public function handle(UpdatedSlugEvent $event): void
    {
        if (
            ! $event->data instanceof BaseModel ||
            ! in_array($event->data::class, Menu::getMenuOptionModels())
        ) {
            return;
        }

        try {
            $query = MenuNode::query()
                ->where([
                    'reference_id' => $event->data->getKey(),
                    'reference_type' => $event->data::class,
                ]);

            $nodes = RepositoryHelper::applyBeforeExecuteQuery($query, $event->data)->get();

            foreach ($nodes as $node) {
                $newUrl = str_replace(url(''), '', $node->reference->url);
                if ($node->url != $newUrl) {
                    $node->url = $newUrl;
                    $node->save();
                }
            }
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }
}
