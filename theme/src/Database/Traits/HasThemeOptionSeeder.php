<?php

namespace Alphasky\Theme\Database\Traits;

use Alphasky\Setting\Facades\Setting;
use Alphasky\Theme\Facades\ThemeOption;

trait HasThemeOptionSeeder
{
    protected function truncateOptions(): void
    {
        Setting::newQuery()->where('key', 'LIKE', ThemeOption::getOptionKey('%'))->delete();
    }

    protected function createThemeOptions(array $options, bool $truncate = true): void
    {
        if ($truncate) {
            $this->truncateOptions();
        }

        Setting::set(ThemeOption::prepareFromArray($options));

        Setting::save();
    }
}
