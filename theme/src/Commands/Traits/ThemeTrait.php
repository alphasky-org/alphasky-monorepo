<?php

namespace Alphasky\Theme\Commands\Traits;

use Alphasky\Theme\Facades\Theme;

trait ThemeTrait
{
    protected function getPath(?string $path = null, ?string $theme = null): string
    {
        $rootPath = theme_path();

        if (! $theme) {
            $theme = $this->getTheme();
        }

        return rtrim($rootPath, '/') . '/' . rtrim(ltrim(strtolower($theme), '/'), '/') . '/' . $path;
    }

    protected function getTheme(): ?string
    {
        if ($this->hasArgument('name')) {
            $name = $this->argument('name');

            if (! $name) {
                return $name;
            }

            return strtolower($name);
        }

        return Theme::getThemeName();
    }
}
