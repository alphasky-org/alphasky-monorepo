<?php

namespace Alphasky\Base\Supports;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Setting\Facades\Setting;
use Illuminate\Foundation\Testing\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->activatePlugins();
    }

    protected function activatePlugins(): void
    {
        $plugins = array_values(BaseHelper::scanFolder(plugin_path()));

        Setting::forceSet('activated_plugins', json_encode($plugins))->save();
    }
}
