<?php

namespace Alphasky\Table\Columns;
use Alphasky\Icon\View\Components\Icon;
use Alphasky\Base\Facades\Html;
use Alphasky\Table\Columns\Concerns\HasLink;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;

class IconColumn extends FormattedColumn implements FormattedColumnContract
{
    use HasLink;

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'icon', $name)
            ->title(trans('core/base::tables.icon'))
            ->alignStart();
    }

    public function formattedValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        $icon = new Icon(name: $value);

        $render = $icon->render();

        return $render(['attributes' => new \Illuminate\View\ComponentAttributeBag()])->toHtml();
    }
}
