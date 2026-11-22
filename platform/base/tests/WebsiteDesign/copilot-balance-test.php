<?php

use Alphasky\Base\Supports\WebsiteDesign\CopilotBalance;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require $argv[2].'/platform/base/src/Supports/WebsiteDesign/CopilotBalance.php';

config(['app.url' => 'https://tenant.example', 'alphasky.server_url' => 'https://builder.example',
    'core.base.general.managed_mail_url' => '', 'core.base.general.website_design.runure_balance_url' => '']);
Http::preventStrayRequests();
Http::fake([
    'https://builder.example/*' => Http::sequence()
        ->push(['data' => ['tokens' => 15062, 'source' => 'site_key']])
        ->push(['data' => ['tokens' => 0, 'source' => 'alphasky_wallet']])
        ->push(['data' => ['activateAlphasky' => '1', 'token_count' => 1000000000]])
        ->push(['data' => ['source' => 'site_key']])
        ->push([], 403),
    'https://wallet.example/*' => Http::response(['data' => ['tokens' => 25000, 'source' => 'runure_wallet']]),
]);
$client = new CopilotBalance();
function checkBalance(mixed $actual, mixed $expected): void {
    if ($actual !== $expected) throw new RuntimeException('Incorrect credit response');
}
checkBalance($client->read('test-key'), ['source' => 'site_key', 'tokens' => 15062]);
checkBalance($client->read('test-key'), ['source' => 'alphasky_wallet', 'tokens' => 0]);
checkBalance($client->read('test-key'), null);
foreach ([503, 403] as $expected) {
    try {
        $client->read('test-key');
        throw new RuntimeException('Missing error');
    } catch (HttpException $e) {
        checkBalance($e->getStatusCode(), $expected);
    }
}
config(['core.base.general.managed_mail_url' => 'https://wallet.example/api/v1/websites/mail-configuration']);
checkBalance($client->read('test-key'), ['source' => 'runure_wallet', 'tokens' => 25000]);
Http::assertSent(fn ($r) => $r->method() === 'GET' && $r->hasHeader('Authorization', 'Bearer test-key')
    && str_starts_with($r->url(), 'https://wallet.example/api/v1/websites/design/balance?domain=tenant.example'));
echo "Copilot credit source, zero, legacy, access, unavailable and managed transport checks passed.\n";
