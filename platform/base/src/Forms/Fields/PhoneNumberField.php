<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Forms\FormField;

class PhoneNumberField extends FormField
{
    protected function getTemplate(): string
    {
        Assets::addStylesDirectly('vendor/core/core/base/libraries/intl-tel-input/css/intlTelInput.min.css')
            ->addScriptsDirectly([
                'vendor/core/core/base/libraries/intl-tel-input/js/intlTelInput.min.js',
                'vendor/core/core/base/js/phone-number-field.js',
            ]);

        return 'core/base::forms.fields.phone-number';
    }
}
