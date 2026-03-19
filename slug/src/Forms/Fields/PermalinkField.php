<?php

namespace Alphasky\Slug\Forms\Fields;

use Alphasky\Base\Forms\FormField;

class PermalinkField extends FormField
{
    protected function getTemplate(): string
    {
        return 'packages/slug::forms.fields.permalink';
    }
}
