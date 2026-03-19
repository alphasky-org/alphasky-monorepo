<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Contracts\PanelSections\Manager;
use Illuminate\Foundation\Events\Dispatchable;

class PanelSectionsRendering
{
    use Dispatchable;

    public function __construct(public Manager $panelSectionManager)
    {
    }
}
