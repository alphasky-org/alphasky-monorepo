<?php

namespace Alphasky\Optimize\Facades;

use Alphasky\Optimize\Supports\Optimizer;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static \Alphasky\Optimize\Supports\Optimizer enable()
 * @method static \Alphasky\Optimize\Supports\Optimizer disable()
 *
 * @see \Alphasky\Optimize\Supports\Optimizer
 */
class OptimizerHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Optimizer::class;
    }
}
