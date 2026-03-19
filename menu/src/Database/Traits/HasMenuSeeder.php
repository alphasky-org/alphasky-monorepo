<?php

namespace Alphasky\Menu\Database\Traits;

use Alphasky\Language\Models\LanguageMeta;
use Alphasky\Menu\Facades\Menu;
use Alphasky\Menu\Models\Menu as MenuModel;
use Alphasky\Menu\Models\MenuLocation;
use Alphasky\Menu\Models\MenuNode;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasMenuSeeder
{
    protected function createMenus(array $data, bool $truncate = true): void
    {
        if ($truncate) {
            MenuModel::query()->truncate();
            MenuLocation::query()->truncate();
            MenuNode::query()->truncate();
        }

        foreach ($data as $item) {
            $item['slug'] = Str::slug($item['name']);

            /**
             * @var MenuModel $menu
             */
            $menu = MenuModel::query()->create(Arr::except($item, ['items', 'location']));

            if (isset($item['location'])) {
                /**
                 * @var MenuLocation $menuLocation
                 */
                $menuLocation = MenuLocation::query()->create([
                    'menu_id' => $menu->getKey(),
                    'location' => $item['location'],
                ]);

                if (is_plugin_active('language')) {
                    LanguageMeta::saveMetaData($menuLocation);
                }
            }

            foreach ($item['items'] as $position => $menuNode) {
                $this->createMenuNode($position, $menuNode, $menu->getKey());
            }

            if (is_plugin_active('language')) {
                LanguageMeta::saveMetaData($menu);
            }

            $this->createMetadata($menu, $item);
        }

        Menu::clearCacheMenuItems();
    }

    protected function createMenuNode(int $position, array $menuNode, int|string $menuId, int|string $parentId = 0): void
    {
        $menuNode['menu_id'] = $menuId;
        $menuNode['parent_id'] = $parentId;
        $menuNode['position'] = $position;

        if (isset($menuNode['url'])) {
            $menuNode['url'] = str_replace(url(''), '', $menuNode['url']);
        }

        if (Arr::has($menuNode, 'children') && ! empty($menuNode['children'])) {
            $children = $menuNode['children'];
            $menuNode['has_child'] = true;
        } else {
            $children = [];
            $menuNode['has_child'] = false;
        }

        Arr::forget($menuNode, 'children');

        /**
         * @var MenuNode $createdNode
         */
        $createdNode = MenuNode::query()->create($menuNode);

        $this->createMetadata($createdNode, $menuNode);

        if ($children) {
            foreach ($children as $childPosition => $child) {
                $this->createMenuNode($childPosition, $child, $menuId, $createdNode->getKey());
            }
        }
    }
}
