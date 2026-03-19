<?php

namespace Alphasky\Menu\Forms;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Forms\FieldOptions\NameFieldOption;
use Alphasky\Base\Forms\FieldOptions\StatusFieldOption;
use Alphasky\Base\Forms\Fields\SelectField;
use Alphasky\Base\Forms\Fields\TextField;
use Alphasky\Base\Forms\FormAbstract;
use Alphasky\Menu\Http\Requests\MenuRequest;
use Alphasky\Menu\Models\Menu;

class MenuForm extends FormAbstract
{
    public function setup(): void
    {
        Assets::addStyles('jquery-nestable')
            ->addScripts('jquery-nestable')
            ->addScriptsDirectly('vendor/core/packages/menu/js/menu.js')
            ->addStylesDirectly('vendor/core/packages/menu/css/menu.css');

        $this
            ->model(Menu::class)
            ->setFormOption('class', 'form-save-menu')
            ->setValidatorClass(MenuRequest::class)
            ->add('name', TextField::class, NameFieldOption::make()->required()->maxLength(120))
            ->add('status', SelectField::class, StatusFieldOption::make())
            ->addMetaBoxes([
                'structure' => [
                    'wrap' => false,
                    'content' => function () {
                        /**
                         * @var Menu $menu
                         */
                        $menu = $this->getModel();

                        return view('packages/menu::menu-structure', [
                            'menu' => $menu,
                            'locations' => $menu->getKey() ? $menu->locations()->pluck('location')->all() : [],
                        ])->render();
                    },
                ],
            ])
            ->setBreakFieldPoint('status');
    }
}
