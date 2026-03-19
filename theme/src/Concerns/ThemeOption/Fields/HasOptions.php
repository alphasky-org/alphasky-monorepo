<?php

namespace Alphasky\Theme\Concerns\ThemeOption\Fields;

trait HasOptions
{
    protected array $options = [];

    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }
}
