<?php

namespace Alphasky\Base\Forms\FieldOptions;

class ContentFieldOption extends EditorFieldOption
{
    public static function make(): static
    {
        return parent::make()
            ->label(trans('core/base::forms.content'))
            ->placeholder(trans('core/base::forms.content_placeholder'));
    }
}
