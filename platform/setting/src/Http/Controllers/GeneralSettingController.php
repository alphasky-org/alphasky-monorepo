<?php
namespace Alphasky\Setting\Http\Controllers;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Base\Supports\Language;
use Alphasky\Setting\Forms\GeneralSettingForm;
use Alphasky\Setting\Http\Requests\GeneralSettingRequest;
use Illuminate\Support\Arr;

class GeneralSettingController extends SettingController
{
    public function edit()
    {
        $this->pageTitle(trans('core/setting::setting.general_setting'));

        $form = GeneralSettingForm::create();

        return view('core/setting::general', compact('form'));
    }

    public function update(GeneralSettingRequest $request): BaseHttpResponse
    {
        $data = Arr::except($request->input(), [
            'locale',
        ]);

        $locale = $request->input('locale');
        if ($locale && array_key_exists($locale, Language::getAvailableLocales())) {
            session()->put('site-locale', $locale);
        }

        $isDemoModeEnabled = BaseHelper::hasDemoModeEnabled();

        if (! $isDemoModeEnabled) {
            $data['locale'] = $locale;
        }

        cache()->forget('core.base.boot_settings');

        return $this->performUpdate($data);
    }

}
