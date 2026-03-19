<?php

namespace Alphasky\Optimize\Http\Controllers\Settings;

use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Optimize\Forms\Settings\OptimizeSettingForm;
use Alphasky\Optimize\Http\Requests\OptimizeSettingRequest;
use Alphasky\Setting\Http\Controllers\SettingController;

class OptimizeSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('packages/optimize::optimize.settings.title'));

        return OptimizeSettingForm::create()->renderForm();
    }

    public function update(OptimizeSettingRequest $request): BaseHttpResponse
    {
        return $this->performUpdate($request->validated());
    }
}
