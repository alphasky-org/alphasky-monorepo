<?php

namespace Alphasky\Table\Columns;

use Alphasky\Base\Facades\Html;
use Alphasky\Table\Columns\Concerns\HasLink;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;

class EmailColumn extends FormattedColumn implements FormattedColumnContract
{
    use HasLink;

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'email', $name)
            ->title(trans('core/base::tables.email'))
            ->alignStart();
    }

    public function formattedValue($value): ?string
    {     
        return  Html::mailto($value, $value, [], true, false);
    }
}
