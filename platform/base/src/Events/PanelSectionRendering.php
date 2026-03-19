<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Contracts\PanelSections\PanelSection;
use Illuminate\Foundation\Events\Dispatchable;

class PanelSectionRendering
{
    use Dispatchable;

    public function __construct(public PanelSection $section)
    {
    }
}
