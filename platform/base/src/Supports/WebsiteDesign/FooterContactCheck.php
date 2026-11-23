<?php

namespace Alphasky\Base\Supports\WebsiteDesign;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FooterContactCheck
{
    public static function verify(string $module): bool
    {
        if (!preg_match('/^design_[a-f0-9]{12}_widgets_[a-f0-9]{12}$/D', $module)) return false;
        $file = plugin_path($module.'/content.json');
        if (!is_file($file)) return false;
        $plan = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        if (empty($plan['footer_contact'])) return false;

        $url = rtrim(config('app.url'), '/').'/';
        $response = Http::connectTimeout(5)->timeout(15)->withoutRedirecting()
            ->withHeaders(['Cache-Control'=>'no-cache'])->get($url, ['footer_check'=>bin2hex(random_bytes(5))]);
        $expected = [];
        foreach ($plan['footer_contact'] as $field) {
            if (!in_array($field, ['address', 'phone'], true)) throw new RuntimeException('Invalid contact field');
            $expected[$field] = $plan['theme_options'][$field] ?? theme_option($field);
        }
        if (!$response->successful() || !self::matches($response->body(), $expected)) {
            throw new RuntimeException('Footer contact could not be verified');
        }
        return true;
    }

    public static function matches(string $html, array $expected): bool
    {
        if ($html === '' || !$expected) return false;
        $document = new DOMDocument();
        if (!$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) return false;
        $xpath = new DOMXPath($document);
        $footer = $xpath->query('//footer')->item(0);
        if (!$footer) return false;
        // Ignore non-visible script/style/template text when checking the published address.
        foreach ($xpath->query('.//script|.//style|.//template|.//*[@hidden]', $footer) as $node) $node->parentNode?->removeChild($node);
        foreach ($expected as $field=>$value) {
            if (!is_string($value) || trim($value) === '') return false;
            if ($field === 'address' && !str_contains(preg_replace('/\s+/u', ' ', $footer->textContent), preg_replace('/\s+/u', ' ', trim($value)))) return false;
            if ($field === 'phone') {
                $digits = strtr($value, array_combine(mb_str_split('٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹'), str_split('01234567890123456789')));
                $href = 'tel:'.preg_replace('/[^+0-9]/', '', $digits);
                $links = $xpath->query('.//a[@href="'.$href.'"]', $footer);
                if (!$links->length || trim($links->item(0)->textContent) === '') return false;
            }
        }
        return true;
    }
}
