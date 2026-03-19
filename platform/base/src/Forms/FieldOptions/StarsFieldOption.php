<?php

namespace Alphasky\Base\Forms\FieldOptions;

class StarsFieldOption extends SelectFieldOption
{
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['values'] = $this->getChoices();

        return $data;
    }
}
