<?php

namespace Alphasky\Widget\Forms;

use Alphasky\Base\Forms\FieldOptions\AlertFieldOption;
use Alphasky\Base\Forms\FieldOptions\SelectFieldOption;
use Alphasky\Base\Forms\Fields\AlertField;
use Alphasky\Base\Forms\Fields\SelectField;
use Alphasky\Base\Forms\FormAbstract;
use Alphasky\Base\Models\BaseModel;
use Alphasky\Widget\AbstractWidget;

class WidgetForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(BaseModel::class)
            ->contentOnly();
    }

    public function renderForm(
        array $options = [],
        bool $showStart = false,
        bool $showFields = true,
        bool $showEnd = false
    ): string {
        return parent::renderForm($options, $showStart, $showFields, $showEnd);
    }

    public function withCaching(bool $caching = true): static
    {
        if (! $caching) {
            $this->remove('enable_caching');

            return $this;
        }

        if (! setting('widget_cache_enabled', false)) {
            return $this;
        }

        $this
            ->remove('enable_caching')
            ->add(
                'enable_caching',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Enable caching'))
                    ->choices([
                        'yes' => __('Yes'),
                        'no' => __('No'),
                    ])
                    ->helperText(
                        __(
                            'When enabled, this widget content will be cached to improve performance. Disable for dynamic content that changes frequently.'
                        )
                    )
            );

        return $this;
    }

    public function withCacheWarning(string $widgetClass): static
    {
        if (in_array($widgetClass, AbstractWidget::getIgnoredCaches())) {
            $this
                ->remove('enable_caching')
                ->add(
                    'cache_warning',
                    AlertField::class,
                    AlertFieldOption::make()
                        ->type('warning')
                        ->content(
                            __(
                                'Due to UI issues, cache for this widget is disabled via code. This widget will not be cached even if caching is enabled.'
                            )
                        )
                );
        }

        return $this;
    }
}
