<?php

namespace Alphasky\Theme\Listeners;

use Alphasky\Setting\Facades\Setting;
use Alphasky\Theme\Facades\Theme;

class SetDefaultTheme
{
    public function handle(): void
    {
        Setting::forceSet('theme', Theme::getThemeName())->set('show_admin_bar', 1)->save();
    }
}
