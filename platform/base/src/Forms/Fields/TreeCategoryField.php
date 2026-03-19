<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Forms\FieldOptions\TreeCategoryFieldOption;
use Alphasky\Base\Forms\FormField;

class TreeCategoryField extends FormField
{
    public function getFieldOption(): string
    {
        return TreeCategoryFieldOption::class;
    }

    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.tree-categories';
    }
}
