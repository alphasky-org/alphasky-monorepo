<?php

namespace Alphasky\Installer\Http\Requests;

use Alphasky\Installer\InstallerStep\InstallerStep;
use Alphasky\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ChooseThemePresetRequest extends Request
{
    public function rules(): array
    {
        return [
            'theme_preset' => ['required', 'string', Rule::in(array_keys(InstallerStep::getThemePresets()))],
        ];
    }
}
