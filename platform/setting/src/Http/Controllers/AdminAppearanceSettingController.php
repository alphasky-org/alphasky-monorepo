<?php

namespace Alphasky\Setting\Http\Controllers;

use Alphasky\Base\Facades\AdminAppearance;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Base\Services\DownloadLocaleService;
use Alphasky\Setting\Forms\AdminAppearanceSettingForm;
use Alphasky\Setting\Http\Requests\AdminAppearanceRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class AdminAppearanceSettingController extends SettingController
{
    public function index()
    {
        $this->pageTitle(trans('core/setting::setting.admin_appearance.title'));

        return AdminAppearanceSettingForm::create()->renderForm();
    }

    public function update(AdminAppearanceRequest $request): BaseHttpResponse
    {
        $localeDirectionKey = AdminAppearance::getSettingKey('locale_direction');
        $localeKey = AdminAppearance::getSettingKey('locale');

        $data = Arr::except($request->validated(), [$localeDirectionKey]);

        $isDemoModeEnabled = BaseHelper::hasDemoModeEnabled();

        $adminLocalDirection = $request->input($localeDirectionKey);

        if ($adminLocalDirection != setting($localeDirectionKey)) {
            session()->put('admin_locale_direction', $adminLocalDirection);
        }

        if (! $isDemoModeEnabled) {
            $data[$localeDirectionKey] = $adminLocalDirection;
        }

        $adminLocale = $request->input($localeKey);
        if ($adminLocale && ! File::exists(lang_path($adminLocale))) {
            try {
                $downloadService = new DownloadLocaleService();
                $downloadService->handle($adminLocale, false);
            } catch (\Throwable $e) {
                BaseHelper::logError($e);
            }
        }

        $this->forceSaveSettings =  ! $isDemoModeEnabled;

        return $this->performUpdate($data);
    }
}
