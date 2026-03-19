<?php

namespace Alphasky\Base\Forms\FieldTypes;

use Alphasky\Base\Traits\Forms\CanSpanColumns;

class CheckableType extends \Kris\LaravelFormBuilder\Fields\CheckableType
{
    use CanSpanColumns;
}
