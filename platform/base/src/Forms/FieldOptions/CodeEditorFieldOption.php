<?php

namespace Alphasky\Base\Forms\FieldOptions;

class CodeEditorFieldOption extends TextareaFieldOption
{
    public function mode(string $mode): static
    {
        $this->addAttribute('mode', $mode);

        return $this;
    }
}
