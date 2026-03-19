<?php

namespace Alphasky\Table\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Table\TableBuilder;

class TableController extends BaseController
{
    public function __construct(protected TableBuilder $tableBuilder)
    {
    }
}
