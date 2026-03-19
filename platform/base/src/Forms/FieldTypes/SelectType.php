<?php

namespace Alphasky\Base\Forms\FieldTypes;

use Alphasky\Base\Traits\Forms\CanSpanColumns;

class SelectType extends \Kris\LaravelFormBuilder\Fields\SelectType
{
    use CanSpanColumns;
}
