<?php

namespace Alphasky\Theme\Forms\Fields;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Forms\FormField;

class ThemeIconField extends FormField
{
    protected function getTemplate(): string
    {
        Assets::addScriptsDirectly('vendor/core/packages/theme/js/icons-field.js');

        return 'packages/theme::fields.icons-field';
    }
}
