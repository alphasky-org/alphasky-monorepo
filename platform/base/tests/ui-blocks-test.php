<?php
namespace Alphasky\Base\Supports\WebsiteDesign {
 function shortcode(){return $GLOBALS['ui_registry'];}
 function theme_option($key){return 1;}
}
namespace {
require $argv[1].'/vendor/autoload.php';$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require_once $argv[2].'/platform/base/src/Supports/WebsiteDesign/DesignUiBlocks.php';
use Alphasky\Base\Supports\WebsiteDesign\DesignUiBlocks;
require_once $argv[2].'/platform/base/src/Supports/WebsiteDesign/DesignPublisher.php';
use Illuminate\Database\Capsule\Manager;use Illuminate\Database\Eloquent\Model;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Http;
$dir=sys_get_temp_dir().'/ui-block-test-'.bin2hex(random_bytes(6));mkdir($dir,0700);$app->useStoragePath($dir);config(['app.url'=>'https://tenant.example']);
$db=new Manager();$db->addConnection(['driver'=>'sqlite','database'=>':memory:']);DB::swap($db->getDatabaseManager());Model::setConnectionResolver($db->getDatabaseManager());
$db->getConnection()->getSchemaBuilder()->create('pages',function($t){$t->id();$t->string('name');$t->string('status');$t->text('content');$t->timestamps();});
$GLOBALS['ui_registry']=new class {
 function getAll(){return ['google-map'=>['name'=>'Map'],'youtube-video'=>['name'=>'Video'],'catalog-list'=>['name'=>'Products','design_schema'=>['kind'=>'public_data']],'private-data'=>['name'=>'Private']];}
 function generateShortcode($name,$attributes,$content){$a='';foreach($attributes as$k=>$v)$a.=' '.$k.'="'.$v.'"';return '['.$name.$a.']'.$content.'[/'.$name.']';}
};
$n=0;function ok($x){global$n;if(!$x)throw new RuntimeException('Assertion failed #'.($n+1));$n++;}
function rejects($f,$code){try{$f();throw new LogicException('expected rejection '.$code);}catch(RuntimeException$e){ok($e->getMessage()===$code);}}
$service=new DesignUiBlocks();$content='<div data-id="builder-keep">Existing content</div>';DB::table('pages')->insert(['id'=>1,'name'=>'Home','status'=>'published','content'=>$content]);
$operation='11111111-2222-4333-8444-555555555555';$block=['name'=>'google-map','content'=>'سحاب دوار الشهيد','attributes'=>['width'=>'100%','height'=>400]];$plan=['page_id'=>1,'content_sha256'=>hash('sha256',$content),'blocks'=>[$block]];
try{
 ok(\Alphasky\Base\Supports\WebsiteDesign\DesignPublisher::homepageContent('[design-home][/design-home]'.$content,'design-home')==='[design-home][/design-home]'.$content);
 $inventory=$service->inventory();ok($inventory['pages'][0]['content_sha256']===$plan['content_sha256']);ok(count($inventory['blocks'])===4);ok($inventory['blocks'][3]['schema']===null);
 foreach([['name'=>'private-data'],['name'=>'missing']]as$b)rejects(fn()=>$service->block($b),'ui_block_not_supported');
 rejects(fn()=>$service->block(array_replace($block,['content'=>'x[/google-map]<script>'])),'unsafe_ui_content');
 rejects(fn()=>$service->block(array_replace($block,['attributes'=>['onclick'=>'bad']])),'invalid_ui_attributes');
 rejects(fn()=>$service->block(array_replace($block,['attributes'=>['height'=>'400;display:none']])),'invalid_ui_dimension');
 rejects(fn()=>$service->block(['name'=>'youtube-video','content'=>'https://evil.example/watch?v=SlPhMPnQ58k']),'invalid_youtube_url');
 ok(str_contains($service->block(['name'=>'youtube-video','content'=>'https://www.youtube.com/watch?v=SlPhMPnQ58k']),'youtube-video'));
 $publication=$service->apply($operation,$plan);$saved=DB::table('pages')->value('content');ok(str_starts_with($saved,$content));ok($service->apply($operation,$plan)===$publication);ok(DB::table('pages')->value('content')===$saved);
 rejects(fn()=>$service->apply('22222222-2222-4333-8444-555555555555',$plan),'page_changed_since_plan');
 ok(json_decode(file_get_contents($dir.'/app/private/website-design/page-backups/'.$operation.'.json'),true)['content']===$content);
 Http::preventStrayRequests();$html='<div data-design-ui="'.$operation.'-0"><iframe src="https://maps.google.com/maps?q='.rawurlencode($block['content']).'&output=embed"></iframe></div>';
 foreach([$html=>true,'<div data-design-ui="'.$operation.'-0">[google-map]x[/google-map]</div>'=>false,str_replace('maps.google.com','evil.example',$html)=>false]as$body=>$valid){
 Http::swap(new Illuminate\Http\Client\Factory());Http::preventStrayRequests();Http::fake(['tenant.example/*'=>Http::response($body)]);
 if($valid){$service->verify($publication);ok(true);}else{try{$service->verify($publication);throw new LogicException('false success');}catch(RuntimeException $e){ok(true);}}
 }
 echo "Passed $n UI block inventory, injection, conflict, preservation, retry and public render checks.\n";
}finally{(new Illuminate\Filesystem\Filesystem())->deleteDirectory($dir);}
}
