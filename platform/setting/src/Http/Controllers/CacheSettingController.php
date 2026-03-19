<?php

namespace Alphasky\Setting\Http\Controllers;

use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Setting\Forms\CacheSettingForm;
use Alphasky\Setting\Http\Requests\CacheSettingRequest;

class CacheSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('core/setting::setting.cache.title'));

        return CacheSettingForm::create()->renderForm();
    }

    public function update(CacheSettingRequest $request): BaseHttpResponse
    {
        return $this->performUpdate($request->validated());
    }
}
