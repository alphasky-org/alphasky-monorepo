<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Forms\FormAbstract;
use Illuminate\Foundation\Events\Dispatchable;

class FormRendering
{
    use Dispatchable;

    public function __construct(public FormAbstract $form)
    {
    }
}
