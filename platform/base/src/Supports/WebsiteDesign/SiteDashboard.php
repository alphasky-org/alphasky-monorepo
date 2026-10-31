<?php

namespace Alphasky\Base\Supports\WebsiteDesign;

use Alphasky\ACL\Models\User;
use Alphasky\Dashboard\Events\RenderingDashboardWidgets;
use Alphasky\Dashboard\Models\DashboardWidget;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/** Private tenant broker only. Uses the CMS registry, never a client-supplied query or endpoint. */
final class SiteDashboard
{
    public function widgets(): array
    {
        $owner = User::query()->where('super_user', 1)->orderBy('id')->first();
        if (! $owner) {
            throw new RuntimeException('control_panel_unavailable');
        }

        $guard = Auth::guard('web');
        $previousUser = $guard->user();
        $previousGuard = Auth::getDefaultDriver();
        $request = request();
        $resolver = $request->getUserResolver();
        try {
            Auth::shouldUse('web');
            $guard->setUser($owner);
            $request->setUserResolver(fn () => $owner);
            RenderingDashboardWidgets::dispatch();
            $settings = DashboardWidget::query()->with(['settings' => fn ($query) => $query
                ->where('user_id', $owner->getKey())->select(['status', 'order', 'settings', 'widget_id'])->oldest('order')])
                ->select(['id', 'name'])->get();
            $registered = apply_filters(DASHBOARD_FILTER_ADMIN_LIST, [], $settings);

            return self::normalize($registered, (string) config('app.url'));
        } finally {
            $previousUser ? $guard->setUser($previousUser) : $guard->forgetUser();
            Auth::shouldUse($previousGuard);
            $request->setUserResolver($resolver);
        }
    }

    /** No arbitrary HTML, scripts, AJAX URLs, or model metadata crosses the broker. */
    public static function normalize(array $registered, string $siteUrl): array
    {
        $result = [];
        foreach ($registered as $widget) {
            $data = $widget['mobile'] ?? null;
            if (! is_array($data) || ! ($data['visible'] ?? false)
                || ! in_array($data['kind'] ?? '', ['stat', 'widget'], true)
                || ! is_string($data['key'] ?? null) || $data['key'] === '') {
                continue;
            }
            $title = self::text($data['title'] ?? '', 250);
            if ($title === '') {
                continue;
            }
            $value = $data['value'] ?? null;
            $url = (string) ($data['url'] ?? '');
            // Same-origin navigation only; app never calls a widget's AJAX route as a command.
            $safeUrl = parse_url($url);
            $origin = parse_url($siteUrl);
            if (! is_array($safeUrl) || ! is_array($origin)
                || ! in_array($safeUrl['scheme'] ?? '', ['https', 'http'], true)
                || ($safeUrl['scheme'] ?? '') !== ($origin['scheme'] ?? '')
                || strcasecmp($safeUrl['host'] ?? '', $origin['host'] ?? '') !== 0
                || ($safeUrl['port'] ?? null) !== ($origin['port'] ?? null)
                || isset($safeUrl['user']) || isset($safeUrl['pass'])) {
                $url = null;
            }
            // AJAX charts need their CMS renderer. Link to the dashboard, not the AJAX response.
            if ($data['kind'] === 'widget') {
                $url = route('dashboard.index');
            }
            $result[$data['key']] = [
                'key' => $data['key'], 'kind' => $data['kind'], 'title' => $title,
                'value' => is_int($value) || is_float($value) ? $value : (is_string($value) ? self::text($value, 200) : null),
                'icon' => preg_match('/^[a-z0-9 -]{1,100}$/iD', (string) ($data['icon'] ?? '')) ? $data['icon'] : '',
                'url' => $url, 'order' => (int) ($data['order'] ?? 999),
            ];
        }
        $result = array_values($result);
        usort($result, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $result;
    }

    private static function text(mixed $value, int $length): string
    {
        return is_scalar($value) ? mb_substr(trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, $length) : '';
    }
}
