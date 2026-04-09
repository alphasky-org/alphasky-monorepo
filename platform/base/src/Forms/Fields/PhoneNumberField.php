<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Forms\FormField;

class PhoneNumberField extends FormField
{
    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.phone-number';
    }

    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        if (isset($this->options['with_country_code_selection']) && $this->options['with_country_code_selection']) {
            $attributes['data-country-code-selection'] = 'true';
        }

        return $attributes;
    }
}
