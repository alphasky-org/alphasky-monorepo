<?php

namespace Alphasky\Base\Supports\WebsiteDesign;

use Illuminate\Support\Facades\Http;

final class CopilotBalance
{
    public function read(string $siteKey): ?array
    {
        $domain = parse_url((string) config('app.url'), PHP_URL_HOST);
        abort_unless($siteKey !== '' && $domain, 403);

        $endpoint = (string) config('core.base.general.website_design.runure_balance_url');
        // Already-provisioned Runure sites predate the separate balance endpoint.
        $mail = (string) config('core.base.general.managed_mail_url');
        if ($endpoint === '' && $mail !== '') {
            abort_unless(str_ends_with($mail, '/api/v1/websites/mail-configuration'), 503);
            $endpoint = substr($mail, 0, -strlen('/mail-configuration')).'/design/balance';
        }
        $managed = $endpoint !== '';
        if (!$managed) {
            $endpoint = rtrim((string) (config('alphasky.server_url') ?: config('core.base.general.website_design.server_url')), '/').'/api/v1/activatekey';
        }
        abort_unless(parse_url($endpoint, PHP_URL_SCHEME) === 'https' && parse_url($endpoint, PHP_URL_HOST)
            && !parse_url($endpoint, PHP_URL_USER) && !parse_url($endpoint, PHP_URL_PASS), 503);

        $client = Http::acceptJson()->connectTimeout(4)->timeout(12)->withoutRedirecting();
        try {
            $response = $managed
                ? $client->withToken($siteKey)->get($endpoint, ['domain' => $domain])
                : $client->withHeaders(['X-API-KEY' => config('alphasky.api_key') ?: config('core.base.general.website_design.api_key')])
                    ->post($endpoint, ['key' => $siteKey, 'domain' => $domain, 'balance_only' => true]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            abort(503);
        }
        abort_if($response->forbidden(), 403);
        abort_unless($response->successful() && !$response->json('error'), 503);
        $data = $response->json('data');
        if (!$managed && (string) ($data['activateAlphasky'] ?? '') === '1') {
            // Older generators can still verify activation, but do not identify the billing source.
            return null;
        }
        abort_unless(is_array($data) && in_array($data['source'] ?? null, $managed ? ['runure_wallet'] : ['site_key', 'alphasky_wallet'], true)
            && is_int($data['tokens'] ?? null) && $data['tokens'] >= 0, 503);
        return ['source' => $data['source'], 'tokens' => $data['tokens']];
    }
}
