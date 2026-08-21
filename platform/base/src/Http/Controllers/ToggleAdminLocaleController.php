<?php

namespace Alphasky\Base\Http\Controllers;

use Alphasky\ACL\Models\UserMeta;
use Alphasky\Base\Facades\AdminHelper;
use Alphasky\Base\Supports\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToggleAdminLocaleController extends BaseController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locales = array_keys(AdminHelper::getAdminLocales());

        $request->validate([
            'locale' => ['required', Rule::in($locales)],
        ]);

        $locale = $request->query('locale');
        $isRtl = (bool) data_get(Language::getAvailableLocales(), "$locale.is_rtl", false);

        UserMeta::setMeta('locale', $locale);
        UserMeta::setMeta('locale_direction', $isRtl ? 'rtl' : 'ltr');

        return redirect()->back();
    }
}
