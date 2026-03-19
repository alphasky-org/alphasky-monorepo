<?php

namespace Alphasky\Base\Events;

use Alphasky\Base\Contracts\PanelSections\PanelSection;
use Illuminate\Foundation\Events\Dispatchable;

class PanelSectionRendered
{
    use Dispatchable;

    public function __construct(public PanelSection $section, public string $content)
    {
    }
}
