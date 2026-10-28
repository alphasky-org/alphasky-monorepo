<?php
require $argv[1].'/vendor/autoload.php';
$app=require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require $argv[2].'/platform/base/src/Supports/WebsiteDesign/SiteConnection.php';
use Illuminate\Support\Facades\Http;
use Alphasky\Base\Supports\WebsiteDesign\SiteConnection;
config(['app.url'=>'https://tenant.example','alphasky.server_url'=>'https://builder.example','alphasky.api_key'=>'test-gateway','core.base.general.website_design.activation_token'=>'test-private-token']);
Http::preventStrayRequests();
Http::fake(['https://builder.example/api/v1/activatekey'=>Http::response(['data'=>['activateAlphasky'=>'1','alphaskyKey'=>'test-private-token','token_count'=>10]]),'https://builder.example/api/v1/plugin-library'=>Http::response(['data'=>[]])]);
$c=new SiteConnection();
if($c->request('activation',['key'=>'injected','domain'=>'other.example'])!==['active'=>true,'remaining_units'=>10])throw new RuntimeException('Secret leaked or response incorrect');
Http::assertSent(fn($r)=>$r['key']==='test-private-token'&&$r['domain']==='tenant.example'&&$r->hasHeader('X-API-KEY','test-gateway'));
$c->request('library',['action'=>'search','query'=>'team','domain'=>'other.example','alphasky_key'=>'injected']);
Http::assertSent(fn($r)=>str_ends_with($r->url(),'plugin-library')&&$r['domain']==='tenant.example'&&$r['alphasky_key']==='test-private-token');
foreach(['urgent','quote','execute'] as $action){try{$c->request('library',['action'=>$action]);throw new LogicException('Paid action allowed');}catch(RuntimeException $e){if($e->getMessage()!=='unsupported_design_operation')throw $e;}}
config(['alphasky.server_url'=>'http://builder.example']);
try{$c->request('activation');throw new LogicException('Insecure request allowed');}catch(RuntimeException $e){if($e->getMessage()!=='site_connection_not_configured')throw $e;}
echo "Passed connection credential isolation, paid-operation rejection and TLS configuration checks.\n";
