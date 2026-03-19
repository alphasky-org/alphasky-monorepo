<?php

namespace Alphasky\Table\Columns;

use Alphasky\Base\Facades\Html;
use Alphasky\Table\Columns\Concerns\HasLink;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;

class PhoneColumn extends FormattedColumn implements FormattedColumnContract
{
    use HasLink;

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'phone', $name)
            ->title(trans('core/base::tables.phone'))
            ->alignStart();
    }

    public function formattedValue($value): ?string
    {
        if (! $this->isLinkable() || ! $value) {
            return $value;
        }

        return Html::link('tel:' . $value, $value);
    }
}
