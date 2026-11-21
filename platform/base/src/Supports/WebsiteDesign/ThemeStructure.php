<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Alphasky\Theme\Facades\Theme;
use Illuminate\Support\Facades\File;

final class ThemeStructure
{
    /** Only static public markup identifiers, never Blade expressions, text or settings values. */
    public static function inventory(): array
    {
        $name = Theme::getThemeName();
        $root = realpath(theme_path($name));
        $views = [];
        if (!$root || !preg_match('/^[a-z0-9_-]+$/iD', $name)) return [];
        foreach (File::allFiles($root) as $file) {
            $path = $file->getRelativePathname();
            if (!preg_match('~^(partials|views|layouts)/[\w/.-]+\.blade\.php$~D', $path)
                || !str_starts_with((string) $file->getRealPath(), $root.DIRECTORY_SEPARATOR) || $file->getSize() > 200000) continue;
            $markup = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|<\?.*?\?>/s', '', $file->getContents());
            preg_match_all('~<(header|footer|nav|section|article|div|main|ul|h[1-6]|p|aside|button|summary|details|a)\b[^<>]*?\bclass=["\']([^"\']*)["\']~i', $markup, $matches, PREG_SET_ORDER);
            $selectors = [];
            foreach ($matches as $m) {
                foreach (preg_split('/\s+/', trim($m[2])) as $class) {
                    if (preg_match('/^[a-zA-Z][\w-]{0,60}$/D', $class)) $selectors['.'.$class] = true;
                }
            }
            $views[$path] = array_slice(array_keys($selectors), 0, 70);
            if (count($views) >= 120) break;
        }
        return ['theme'=>$name,'views'=>$views];
    }
}
