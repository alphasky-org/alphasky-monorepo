<?php

namespace Alphasky\Theme\ThemeOption\Fields;

use Alphasky\Theme\ThemeOption\ThemeOptionField;

class TextField extends ThemeOptionField
{
    protected ?string $placeholder = null;

    public function fieldType(): string
    {
        return 'text';
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function toArray(): array
    {
        $options = [
            'class' => 'form-control',
        ];

        if ($this->placeholder) {
            $options['placeholder'] = $this->placeholder;
        }

        return [
            ...parent::toArray(),
            'attributes' => [
                ...parent::toArray()['attributes'],
                'value' => $this->getValue(),
                'options' => $options,
            ],
        ];
    }
}
