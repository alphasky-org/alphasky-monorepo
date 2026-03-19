<?php

namespace Alphasky\Table\Columns;

use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;
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
        $name = $this->getColumn()->name;
    
       
      if(is_numeric($value)){
        
        $dataNew = getSelectedChoice('',$name,$value);
       
            if ($dataNew) {
                return ' <span class="badge badge-primary badge-'.$name.'-'.$value.'">'.$dataNew.'</span> ';
            } else {
                $dataNew1 = getSelectedChoice('',$name,$value);
                if ($dataNew1) {
                    return ' <span class="badge badge-primary badge-'.$name.'-'.$value.'">'.$dataNew1.'</span> ';
                } else {
                    return ' <span class="badge badge-primary>' . $value .'</span> ';
                }
            }

        } else {
           
            $return ='';
            $checkarray = json_decode($value);
            if (is_array($checkarray)) {
                foreach ($checkarray as $val) {
                    $dataNew = getSelectedChoice('',$name,$val);
                    
                    if ($dataNew) {
                        $return .= ' <span class="badge badge-primary badge-'.$name.'-'.$val.'">'.$dataNew.'</span> ';
                    } else {
                        $return .= ' <span class="badge badge-primary>' . $value .'</span> ';
                    }
                }
                return $return;
            }

        }
        return  $value;

            /*
        if ($value instanceof BackedEnum) {
        return $value->value;
        }

        $table = $this->getTable();

        if ($table->isExportingToExcel() || $table->isExportingToCSV()) {
        return $value->getValue();
        }

        return $value->toHtml() ?: $value->getValue();
        */
    }
}
