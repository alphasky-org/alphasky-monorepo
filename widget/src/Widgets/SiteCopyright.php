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
            'name' => trans('packages/widget::widget.widget_site_copyright'),
            'description' => trans('packages/widget::widget.widget_site_copyright_description'),
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
                        trans('packages/widget::widget.widget_site_copyright_helper', [
                            'link' => Html::link(route('theme.options'), trans('packages/widget::widget.theme_options')),
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
