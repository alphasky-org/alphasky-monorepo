<?php

namespace Alphasky\Theme\ThemeOption\Fields;

use Alphasky\Theme\ThemeOption\ThemeOptionField;

class IconField extends ThemeOptionField
{
    public function fieldType(): string
    {
        return 'coreIcon';
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'attributes' => [
                ...parent::toArray()['attributes'],
                'value' => $this->value,
            ],
        ];
    }
}
