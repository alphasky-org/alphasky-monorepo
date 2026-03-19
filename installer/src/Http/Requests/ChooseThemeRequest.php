<?php

namespace Alphasky\Installer\Http\Requests;

use Alphasky\Installer\InstallerStep\InstallerStep;
use Alphasky\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ChooseThemeRequest extends Request
{
    public function rules(): array
    {
        return [
            'theme' => ['required', 'string', Rule::in(array_keys(InstallerStep::getThemes()))],
        ];
    }
}
