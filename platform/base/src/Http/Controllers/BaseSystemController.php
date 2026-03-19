<?php

namespace Alphasky\Base\Http\Controllers;

use Alphasky\Base\Supports\Breadcrumb;

class BaseSystemController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(
                trans('core/base::base.panel.platform_administration'),
                route('system.index')
            );
    }
}
