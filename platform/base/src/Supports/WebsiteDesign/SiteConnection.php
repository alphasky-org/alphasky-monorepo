<?php

namespace Alphasky\Base\Supports\WebsiteDesign;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** The caller must authenticate the owner before using this server-side client. */
final class SiteConnection
{
    public function request(string $operation, array $payload = []): array
    {
        $paths = ['activation' => 'activatekey', 'library' => 'plugin-library'];
        if (!isset($paths[$operation])) {
            throw new RuntimeException('unsupported_design_operation');
        }
        $server = rtrim((string) config('alphasky.server_url'), '/');
        $token = (string) config('core.base.general.website_design.activation_token');
        $gateway = (string) config('alphasky.api_key');
        $domain = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if (!filter_var($server, FILTER_VALIDATE_URL) || parse_url($server, PHP_URL_SCHEME) !== 'https'
            || parse_url($server, PHP_URL_USER) || parse_url($server, PHP_URL_PASS)
            || parse_url($server, PHP_URL_QUERY) || parse_url($server, PHP_URL_FRAGMENT)
            || $token === '' || $gateway === '' || $domain === '') {
            throw new RuntimeException('site_connection_not_configured');
        }
        // Explicit allowlists: never proxy credentials, ownership or arbitrary operations from a browser.
        if ($operation === 'library') {
            if (!in_array($payload['action'] ?? '', ['search', 'list', 'status', 'wait'], true)) {
                throw new RuntimeException('unsupported_design_operation');
            }
            $payload = array_intersect_key($payload, array_flip(['action', 'query', 'request_id']));
        } else {
            $payload = [];
        }
        $payload += ['domain' => $domain, $operation === 'activation' ? 'key' : 'alphasky_key' => $token];
        try {
            $response = Http::connectTimeout(10)->timeout(45)->withoutRedirecting()
                ->withHeaders(['X-API-KEY' => $gateway])->acceptJson()->post($server.'/api/v1/'.$paths[$operation], $payload);
        } catch (\Throwable) {
            throw new RuntimeException('alphasky_connection_unavailable');
        }
        if (!$response->successful() || $response->json('error')) {
            throw new RuntimeException($response->status() === 403 ? 'site_activation_required' : 'alphasky_connection_unavailable');
        }
        $data = $response->json('data');
        if (!is_array($data)) {
            throw new RuntimeException('invalid_alphasky_response');
        }
        if ($operation === 'activation') {
            if ((string) ($data['activateAlphasky'] ?? '') !== '1') {
                throw new RuntimeException('site_activation_required');
            }
            // The upstream legacy response echoes the secret; never return it to callers.
            return ['active' => true, 'remaining_units' => max(0, (int) ($data['token_count'] ?? 0))];
        }
        return $data;
    }
}
