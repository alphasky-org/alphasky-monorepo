<?php

namespace Alphasky\Table\Columns;

use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;
use BackedEnum;
use Throwable;

class ChoiceColumn extends FormattedColumn implements FormattedColumnContract
{
    public static function make(array | string $data = [], string $name = ''): static
    {
        return parent::make($data, $name)
            ->alignCenter()
            ->width(100)
            ->withEmptyState()
            ->renderUsing(function (ChoiceColumn $column, $value) {
                try {
                    return $column->formattedValue($value);
                } catch (Throwable) {
                    return $value;
                }
            });
    }

    public function formattedValue($value): ?string
    {
      
    
       
        if ($value instanceof BackedEnum) {
        return $value->value;
        }

        $table = $this->getTable();

        if ($table->isExportingToExcel() || $table->isExportingToCSV()) {
        return $value->getValue();
        }

        return $value->toHtml() ?: $value->getValue();
        
    }
}
