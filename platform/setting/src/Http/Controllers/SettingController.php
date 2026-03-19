<?php

namespace Alphasky\Setting\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Base\Supports\Breadcrumb;
use Alphasky\Setting\Http\Controllers\Concerns\InteractsWithSettings;

abstract class SettingController extends BaseController
{
    use InteractsWithSettings;

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('core/setting::setting.title'), route('settings.index'));
    }
}
