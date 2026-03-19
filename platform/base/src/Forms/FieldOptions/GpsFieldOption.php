<?php

namespace Alphasky\Base\Forms\FieldOptions;

use Alphasky\Base\Forms\FormFieldOptions;
use Closure;

class GpsFieldOption extends FormFieldOptions
{
   



    public function values(array $data): static
    {
      
        $this->values = $data instanceof Closure ? getLatlng() : $data;

        return $this;
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'values' => $this->values,
        ];
    }
}
