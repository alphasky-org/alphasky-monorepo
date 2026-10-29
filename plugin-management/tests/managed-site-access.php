<?php
// php managed-site-access.php /path/to/bootable-cms
require $argv[1].'/vendor/autoload.php';
require dirname(__DIR__).'/src/Services/MarketplaceService.php';
$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;
use Alphasky\PluginManagement\Services\MarketplaceService;
Http::preventStrayRequests();Http::fake(['https://central.example/marketplace/*'=>Http::response(['data'=>[]],200)]);
config(['packages.plugin-management.general.enable_marketplace_feature'=>true,'core.base.general.website_design.activation_token'=>'private-test-site-token','app.url'=>'https://customer.example']);
$ref=new ReflectionClass(MarketplaceService::class);$service=$ref->newInstanceWithoutConstructor();
foreach(['url'=>'https://central.example/marketplace','token'=>'gateway','productId'=>'cms']as$key=>$value)$ref->getProperty($key)->setValue($service,$value);
$service->callApi('get','/products',['alphasky_key'=>'browser-key','site_url'=>'https://attacker.example','domain'=>'attacker.example']);
Http::assertSent(fn($r)=>$r['alphasky_key']==='private-test-site-token'&&$r['site_url']==='https://customer.example'&&$r['domain']==='customer.example');
config(['core.base.general.website_design.activation_token'=>null]);$service->callApi('get','/products',['alphasky_key'=>'legacy-key']);
Http::assertSent(fn($r)=>$r['alphasky_key']==='legacy-key');
echo "PASS: Managed marketplace credentials and site identity override browser input; legacy activation remains supported.\n";
