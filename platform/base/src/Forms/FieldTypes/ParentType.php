<?php

namespace Alphasky\Base\Forms\FieldTypes;

use Alphasky\Base\Traits\Forms\CanSpanColumns;

abstract class ParentType extends \Kris\LaravelFormBuilder\Fields\ParentType
{
    use CanSpanColumns;
}
