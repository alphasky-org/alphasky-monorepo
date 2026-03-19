<?php

namespace Alphasky\Base\Listeners;

use Alphasky\Base\Events\PanelSectionsRendering;
use Alphasky\Base\Facades\DashboardMenu;
use Alphasky\Base\Facades\PanelSectionManager;
use Alphasky\Base\PanelSections\PanelSectionItem;
use Alphasky\Base\PanelSections\System\SystemPanelSection;

class PushDashboardMenuToSystemPanel
{
    public function handle(PanelSectionsRendering $event): void
    {
        $groupId = $event->panelSectionManager->getGroupId();

        if ($groupId !== 'system') {
            return;
        }

        $menuItems = DashboardMenu::getItemsByParentId('cms-core-platform-administration');

        foreach ($menuItems as $menuItem) {
            if (empty($menuItem['name'])) {
                continue;
            }

            if (! empty($menuItem['children'])) {
                foreach ($menuItem['children'] as $child) {
                    $this->registerPanel($child, $groupId);
                }

                continue;
            }

            $this->registerPanel($menuItem, $groupId);
        }
    }

    protected function registerPanel(array $menuItem, string $groupId): void
    {
        PanelSectionManager::group($groupId)
            ->registerItem(
                SystemPanelSection::class,
                fn () => PanelSectionItem::make($menuItem['id'])
                    ->setTitle(trans($menuItem['name']))
                    ->withDescription(trans($menuItem['description'] ?? ''))
                    ->withIcon($menuItem['icon'] ?? 'ti ti-settings')
                    ->withPriority(2500 + ($menuItem['priority'] ?? 500))
                    ->withPermissions($menuItem['permissions'] ?? [])
                    ->withUrl($menuItem['url'] ?? '#')
            );
    }
}
