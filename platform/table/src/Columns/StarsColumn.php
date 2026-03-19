<?php

namespace Alphasky\Table\Columns;

use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;
use Throwable;

class StarsColumn extends FormattedColumn implements FormattedColumnContract
{
    public static function make(array | string $data = [], string $name = ''): static
    {
        return parent::make($data, $name)
            ->alignCenter()
            ->width(100)
            ->withEmptyState()
            ->renderUsing(function (StarsColumn $column, $value) {
                try {
                    return $column->formattedValue($value);
                } catch (Throwable) {
                    return $value;
                }
            });
    }

    public function formattedValue($value): ?string
    {
        $name = $this->getColumn()->name;
    
       
       
                return '
                <div class="testimonials-one__icons" data-stars="'.$value.'"></div>
                 ';
         
          

         
    }
    
}
