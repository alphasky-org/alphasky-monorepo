<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Forms\FormField;

class GpsField extends FormField
{
 

    protected function getTemplate(): string
    {
        //dd($this->parent->getModel());
        return 'core/base::forms.fields.gps';
    }
}
