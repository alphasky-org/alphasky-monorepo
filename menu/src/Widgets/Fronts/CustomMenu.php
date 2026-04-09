<?php

namespace Alphasky\Menu\Widgets\Fronts;

use Alphasky\Base\Forms\FieldOptions\NameFieldOption;
use Alphasky\Base\Forms\FieldOptions\SelectFieldOption;
use Alphasky\Base\Forms\Fields\SelectField;
use Alphasky\Base\Forms\Fields\TextField;
use Alphasky\Base\Supports\RepositoryHelper;
use Alphasky\Menu\Models\Menu;
use Alphasky\Menu\Models\Menu as MenuModel;
use Alphasky\Widget\AbstractWidget;
use Alphasky\Widget\Forms\WidgetForm;

class CustomMenu extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => trans('packages/menu::menu.widget_custom_menu'),
            'description' => trans('packages/menu::menu.widget_custom_menu_description'),
            'menu_id' => null,
        ]);
    }

    protected function settingForm(): WidgetForm|string|null
    {
        $menus = MenuModel::query()
            ->wherePublished();

        $menus = RepositoryHelper::applyBeforeExecuteQuery($menus, new Menu())
            ->pluck('name', 'slug')
            ->all();

        return WidgetForm::createFromArray($this->getConfig())
            ->add('name', TextField::class, NameFieldOption::make())
            ->add(
                'menu_id',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('packages/menu::menu.select_menu'))
                    ->choices($menus)
                    ->searchable()
            );
    }
}
