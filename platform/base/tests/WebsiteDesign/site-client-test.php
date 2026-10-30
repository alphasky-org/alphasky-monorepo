<?php
require $argv[1].'/vendor/autoload.php';$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require_once $argv[2].'/platform/base/src/Supports/WebsiteDesign/DesignSiteClient.php';
use Illuminate\Support\Facades\Http;use Alphasky\Base\Supports\WebsiteDesign\DesignSiteClient;
Http::preventStrayRequests();$dir=sys_get_temp_dir().'/design-client-test-'.bin2hex(random_bytes(6));mkdir($dir,0700);$app->useStoragePath($dir);
$c=['server'=>'https://builder.example','gateway'=>'test-gateway','token'=>'private-token','domain'=>'tenant.example','admission'=>'test-admission','admission_time'=>'123'];$n=0;
function ok($v){global $n;if(!$v)throw new RuntimeException('Assertion failed');$n++;}
function rejects($f,$expected){try{$f();throw new LogicException('Expected rejection');}catch(RuntimeException $e){ok($e->getMessage()===$expected);}}
try{
 Http::fake(['https://builder.example/api/v1/website-design'=>Http::response(['data'=>['messages'=>[]]])]);$client=new DesignSiteClient($c);$client->request(['action'=>'history','domain'=>'attacker.example','alphasky_key'=>'injected']);
 Http::assertSent(fn($r)=>$r['domain']==='tenant.example'&&$r['alphasky_key']==='private-token'&&$r->hasHeader('X-Design-Admission','test-admission'));$n++;
 rejects(fn()=>new DesignSiteClient(array_merge($c,['server'=>'http://builder.example'])),'invalid_connection');
 $module='design_aabbccddeeff_frontend';$path='/api/v1/plugin-package/1?signature=abc';$p=['path'=>$path,'sha256'=>str_repeat('0',64)];Http::fake(['https://builder.example/api/v1/plugin-package/*'=>Http::response('bad zip')]);rejects(fn()=>$client->install($module,$p),'release_hash_mismatch');
 $file=$dir.'/unsafe.zip';$z=new ZipArchive();$z->open($file,ZipArchive::CREATE);$z->addFromString('../escape.php','<?php echo 1;');$z->close();$bytes=file_get_contents($file);Http::swap(new Illuminate\Http\Client\Factory());Http::preventStrayRequests();Http::fake(['https://builder.example/api/v1/plugin-package/*'=>Http::response($bytes)]);$p['sha256']=hash('sha256',$bytes);rejects(fn()=>$client->install($module,$p),'unsafe_archive');ok(!is_file($dir.'/escape.php'));
 rejects(fn()=>$client->install('../escape',$p),'invalid_release');$p['path']='https://attacker.example/package';rejects(fn()=>$client->install($module,$p),'invalid_release');
 $oldBase=$app->basePath();$site=$dir.'/fresh-site';mkdir($site);$app->setBasePath($site);$method=new ReflectionMethod($client,'preparePluginDirectory');$method->invoke($client);ok(is_dir($site.'/platform/plugins'));rmdir($site.'/platform/plugins');symlink($dir,$site.'/platform/plugins');rejects(fn()=>$method->invoke($client),'unsafe_plugin_directory');unlink($site.'/platform/plugins');$app->setBasePath($oldBase);

 $verify=new ReflectionMethod($client,'verifyHomepage');$revision=str_repeat('a',64);$query='سحاب دوار الشهيد';$expected=['revision'=>$revision,'layout'=>'modern','map_query'=>$query];
 $url='https://www.google.com/maps?q='.rawurlencode($query).'&output=embed';
 $html='<div data-design-home data-design-revision="'.$revision.'" data-layout="modern"><iframe src="'.htmlspecialchars($url,ENT_QUOTES).'"></iframe></div>';
 $verify->invoke($client,$html,$expected);ok(true);
 foreach([str_replace($revision,str_repeat('b',64),$html),str_replace('iframe','span',$html),str_replace('google.com','evil.example',$html),str_replace('modern','classic',$html),'<!--'.$html.'-->']as$bad)rejects(fn()=>$verify->invoke($client,$bad,$expected),'site_verification_failed');
 rejects(fn()=>$verify->invoke($client,$html,['revision'=>'bad','layout'=>'modern']),'invalid_homepage_verification');
 $verify->invoke($client,'legacy',null);ok(true);

 $base=$app->basePath();$app->setBasePath($site);$record=$site.'/commands.jsonl';
 file_put_contents($site.'/artisan','<?php file_put_contents('.var_export($record,true).',json_encode($argv).PHP_EOL,FILE_APPEND);');
 $refresh=new ReflectionMethod($client,'refreshPublishedContent');$refresh->invoke($client);
 $calls=array_map(fn($line)=>json_decode($line,true)[1],file($record,FILE_IGNORE_NEW_LINES));ok($calls===['view:clear','cache:clear']);
 file_put_contents($site.'/artisan','<?php exit(1);');rejects(fn()=>$refresh->invoke($client),'site_cache_refresh_failed');$app->setBasePath($base);
 echo "Passed $n site-client credential, TLS, package hash and path checks.\n";
}finally{(new Illuminate\Filesystem\Filesystem())->deleteDirectory($dir);}
