<?php

namespace Alphasky\Widget\Widgets;

use Alphasky\Base\Facades\Html;
use Alphasky\Base\Forms\FieldOptions\HtmlFieldOption;
use Alphasky\Base\Forms\Fields\HtmlField;
use Alphasky\Theme\Supports\ThemeSupport;
use Alphasky\Widget\AbstractWidget;
use Alphasky\Widget\Forms\WidgetForm;
use Illuminate\Support\Collection;

class SiteCopyright extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Site Copyright'),
            'description' => __('Copyright text at the bottom footer.'),
        ]);
    }

    protected function settingForm(): WidgetForm|string|null
    {
        return WidgetForm::createFromArray($this->getConfig())
            ->add(
                'description',
                HtmlField::class,
                HtmlFieldOption::make()
                    ->content(
                        __('Go to :link to change the copyright text.', [
                            'link' => Html::link(route('theme.options'), __('Theme options')),
                        ])
                    )
            );
    }

    protected function data(): array|Collection
    {
        return [
            'copyright' => ThemeSupport::getSiteCopyright(),
        ];
    }
}
