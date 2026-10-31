<?php
namespace Alphasky\Base\Supports\WebsiteDesign { function shortcode(){return $GLOBALS['panel_shortcode'];} function theme_option($key){return $GLOBALS['panel_homepage']??1;} }
namespace {
require $argv[1].'/vendor/autoload.php';$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require $argv[2].'/platform/base/src/Supports/WebsiteDesign/SiteControlPanel.php';require $argv[2].'/platform/base/src/Supports/WebsiteDesign/DesignPublisher.php';
use Alphasky\Base\Supports\WebsiteDesign\SiteControlPanel;use Alphasky\Base\Supports\WebsiteDesign\DesignPublisher;use Illuminate\Database\Capsule\Manager;use Illuminate\Database\Eloquent\Model;use Illuminate\Support\Facades\DB;
$dir=sys_get_temp_dir().'/site-panel-'.bin2hex(random_bytes(6));mkdir($dir,0700);$app->useStoragePath($dir);config(['cache.default'=>'array']);
$db=new Manager();$db->addConnection(['driver'=>'sqlite','database'=>':memory:']);DB::swap($db->getDatabaseManager());Model::setConnectionResolver($db->getDatabaseManager());
$schema=$db->getConnection()->getSchemaBuilder();$schema->create('pages',function($t){$t->id();$t->string('name');$t->text('content');$t->string('status');$t->timestamps();});$schema->create('slugs',function($t){$t->id();$t->string('key');$t->string('prefix')->default('');$t->string('reference_type');$t->integer('reference_id');$t->timestamps();});
$compiler=new Alphasky\Shortcode\Compilers\ShortcodeCompiler();$GLOBALS['panel_shortcode']=new Alphasky\Shortcode\Shortcode($compiler);
$fields=[['name'=>'title','type'=>'text','default'=>'Default title'],['name'=>'rows','type'=>'repeater','fields'=>[['name'=>'text','type'=>'text']],'default'=>[]]];
shortcode(); // bootstrap helper can use its own registry; the panel uses the test namespace registry.
$GLOBALS['panel_shortcode']->register('test-about','About','',fn()=>'');$GLOBALS['panel_shortcode']->setDesignSchema('test-about',['kind'=>'content','fields'=>$fields]);
$GLOBALS['panel_shortcode']->register('test-services','Services','',fn()=>'');$GLOBALS['panel_shortcode']->setDesignSchema('test-services',['kind'=>'content','fields'=>$fields]);
$n=0;function ok($v){global$n;if(!$v)throw new RuntimeException('Assertion failed '.($n+1));$n++;}function rejects($fn,$message){try{$fn();throw new LogicException('expected rejection');}catch(RuntimeException $e){ok($e->getMessage()===$message);}}
try{
 $name='design-123456abcdef-frontend-home';$sections=array_map(fn($s)=>'design-123456abcdef-frontend-'.$s,['navigation','slider','about','services','contact','footer']);
 $raw='<p>Keep</p>['.$name.' headline="Custom title"][/'.$name.']<div>[google-map]Sahab[/google-map]</div>';
 $split=DesignPublisher::homepageContent($raw,$name,$sections);ok(!str_contains($split,'['.$name));ok(substr_count($split,'headline="Custom title"')===6);ok(str_contains($split,'<p>Keep</p>'));ok(str_contains($split,'[google-map]Sahab[/google-map]'));ok(DesignPublisher::homepageContent($split,$name,$sections)===$split);
 $reordered='['.$sections[3].'][/'.$sections[3].']';ok(DesignPublisher::homepageContent($reordered,$name,$sections)===$reordered);
 $content='<p>Original HTML</p>[test-about title="A"][/test-about]\n[test-services title="B"][/test-services]<p>Tail</p>';
 DB::table('pages')->insert(['id'=>1,'name'=>'Home','content'=>$content,'status'=>'published']);DB::table('slugs')->insert(['reference_type'=>Alphasky\Page\Models\Page::class,'reference_id'=>1,'key'=>'home']);
 $service=new SiteControlPanel();$read=new ReflectionMethod($service,'page');$save=new ReflectionMethod($service,'savePage');
 $page=$read->invoke($service,1);ok(count($page['blocks'])===2);ok($page['blocks'][0]['values']['title']==='A');$indices=array_column($page['blocks'],'index');
 $input=['id'=>1,'name'=>'Updated','slug'=>'new-home','version'=>$page['version'],'order'=>array_reverse($indices),'edits'=>[$indices[0]=>['title'=>'Updated text','rows'=>[['text'=>'Row 1']]]]];
 $result=$save->invoke($service,$input);$raw=DB::table('pages')->value('content');ok(str_starts_with($raw,'<p>Original HTML</p>[test-services'));ok(str_ends_with($raw,'<p>Tail</p>'));ok($result['name']==='Updated');ok($result['slug']==='new-home');ok($result['blocks'][1]['values']['rows'][0]['text']==='Row 1');
 rejects(fn()=>$save->invoke($service,$input),'page_changed');
 $input['version']=$result['version'];$input['order']=[$indices[0],$indices[0]];rejects(fn()=>$save->invoke($service,$input),'invalid_block_order');
 rejects(fn()=>SiteControlPanel::validateFields($fields,['title'=>'[evil]']),'invalid_block_fields');rejects(fn()=>SiteControlPanel::validateFields($fields,['title'=>'<script>']),'invalid_block_fields');rejects(fn()=>SiteControlPanel::validateFields($fields,['secret'=>'x']),'invalid_block_fields');
 ok(count(glob($dir.'/app/private/site-control/*.json'))===1);ok(DB::table('pages')->value('content')===$raw);
 DB::table('pages')->insert(['id'=>2,'name'=>'New homepage','content'=>'[test-about][/test-about]','status'=>'published']);$GLOBALS['panel_homepage']=2;
 $blank=$read->invoke($service,2);ok($blank['homepage']);ok($blank['slug']==='');
 $saved=$save->invoke($service,['id'=>2,'name'=>$blank['name'],'slug'=>'','version'=>$blank['version'],'order'=>[0],'edits'=>[0=>['title'=>'New homepage title']]]);ok($saved['blocks'][0]['values']['title']==='New homepage title');
 echo "Passed $n split migration, data editing, ordering, preservation, stale-save, validation and backup checks.\n";
}finally{(new Illuminate\Filesystem\Filesystem())->deleteDirectory($dir);}
}
