<?php

namespace Alphasky\Setting\Listeners;

use Alphasky\Base\Events\PanelSectionsRendering;
use Alphasky\Base\Facades\DashboardMenu;
use Alphasky\Base\Facades\PanelSectionManager;
use Alphasky\Base\PanelSections\PanelSectionItem;
use Alphasky\Setting\PanelSections\SettingOthersPanelSection;
use Closure;

class PushDashboardMenuToOtherSectionPanel
{
    public function handle(PanelSectionsRendering $event): void
    {
        if ($event->panelSectionManager->getGroupId() !== 'settings') {
            return;
        }

        $menuItems = DashboardMenu::getItemsByParentId('cms-core-settings');

        foreach ($menuItems as $menuItem) {
            if (empty($menuItem['name'])) {
                continue;
            }

            if (! empty($menuItem['children'])) {
                foreach ($menuItem['children'] as $child) {
                    $this->registerPanel($child);
                }

                continue;
            }

            $this->registerPanel($menuItem);
        }
    }

    protected function registerPanel(array $menuItem): void
    {
        $menuUrl = $menuItem['url'] instanceof Closure ? $menuItem['url']() : $menuItem['url'];

        if (! $menuUrl) {
            $menuUrl = '#';
        }

        PanelSectionManager::default()
            ->registerItem(
                SettingOthersPanelSection::class,
                fn () => PanelSectionItem::make($menuItem['id'])
                    ->setTitle(trans($menuItem['name']))
                    ->withDescription(trans($menuItem['description'] ?? ''))
                    ->withIcon($menuItem['icon'] ?? 'ti ti-settings')
                    ->withPriority($menuItem['priority'] ?? 500)
                    ->withPermissions($menuItem['permissions'] ?? [])
                    ->withUrl($menuUrl)
            );
    }
}
