<?php

namespace Alphasky\Base\Forms\FieldTypes;

use Alphasky\Base\Traits\Forms\CanSpanColumns;

class StaticType extends \Kris\LaravelFormBuilder\Fields\StaticType
{
    use CanSpanColumns;
}
