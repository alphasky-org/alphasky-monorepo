<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Forms\FormField;

class CoreIconField extends FormField
{
    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.core-icon';
    }
}
