<?php

namespace Alphasky\Base\Forms\FieldTypes;

use Alphasky\Base\Traits\Forms\CanSpanColumns;

class RepeatedType extends \Kris\LaravelFormBuilder\Fields\RepeatedType
{
    use CanSpanColumns;
}
