<?php
require $argv[1].'/vendor/autoload.php';
$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require $argv[2].'/platform/dashboard/src/Supports/DashboardWidgetInstance.php';
require $argv[2].'/platform/base/src/Supports/WebsiteDesign/SiteDashboard.php';
use Alphasky\Base\Supports\WebsiteDesign\SiteDashboard;
use Alphasky\Dashboard\Supports\DashboardWidgetInstance;
use Alphasky\Dashboard\Events\RenderingDashboardWidgets;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
config(['cache.default'=>'array','app.url'=>'https://example.test']);
$db=new Manager();$db->addConnection(['driver'=>'sqlite','database'=>':memory:']);DB::swap($db->getDatabaseManager());Model::setConnectionResolver($db->getDatabaseManager());$app->instance('db',$db->getDatabaseManager());
$schema=$db->getConnection()->getSchemaBuilder();
$schema->create('users',function($t){$t->id();$t->string('first_name');$t->string('last_name');$t->string('email');$t->boolean('super_user');});
$schema->create('dashboard_widgets',function($t){$t->id();$t->string('name');$t->timestamps();});
$schema->create('dashboard_widget_settings',function($t){$t->id();$t->integer('widget_id');$t->integer('user_id');$t->integer('status');$t->integer('order');$t->text('settings')->nullable();$t->timestamps();});
DB::table('users')->insert(['id'=>1,'first_name'=>'Test','last_name'=>'Admin','email'=>'admin@example.test','super_user'=>1]);
Event::forget(RenderingDashboardWidgets::class);remove_filter(DASHBOARD_FILTER_ADMIN_LIST);
$total=7;$extra=false;
Event::listen(RenderingDashboardWidgets::class,function()use(&$total,&$extra){
 remove_filter(DASHBOARD_FILTER_ADMIN_LIST);
 add_filter(DASHBOARD_FILTER_ADMIN_LIST,function($items,$settings)use(&$total,&$extra){
  (new DashboardWidgetInstance())->setType('stats')->setKey('books')->setTitle('Books')->setPermission('books.index')->setRoute('https://example.test/admin/books')->setPriority(20)->setStatsTotal(fn()=>$total)->init($items,$settings);
  if($extra)(new DashboardWidgetInstance())->setType('stats')->setKey('loans')->setTitle('Loans')->setPermission('loans.index')->setRoute('https://example.test/admin/loans')->setPriority(10)->setStatsTotal(3)->init($items,$settings);
  return $items;
 },15,2);
});
$n=0;function ok($value){global$n;if(!$value)throw new RuntimeException('Assertion failed '.($n+1));$n++;}
$service=new SiteDashboard();$original=Auth::guard('web')->user();$data=$service->widgets();ok(count($data)===1);ok($data[0]['key']==='books');ok($data[0]['value']===7);ok(!isset($data[0]['view']));ok(Auth::guard('web')->user()===$original);
$total=12;$extra=true;$data=$service->widgets();ok(count($data)===2);ok($data[0]['key']==='loans');ok($data[1]['value']===12);
$id=DB::table('dashboard_widgets')->where('name','books')->value('id');DB::table('dashboard_widget_settings')->insert(['widget_id'=>$id,'user_id'=>1,'status'=>0,'order'=>0]);$data=$service->widgets();ok(count($data)===1);ok($data[0]['key']==='loans');
$denied=new class extends Alphasky\ACL\Models\User {public function hasPermission(string|array $permissions):bool{return false;}};Auth::guard('web')->setUser($denied);$items=[];(new DashboardWidgetInstance())->setType('stats')->setPermission('secret')->init($items,collect());ok($items===[]);Auth::guard('web')->forgetUser();
$raw=['mobile'=>['key'=>'test','kind'=>'stat','title'=>'<b>Revenue</b>','value'=>'$25.50','url'=>'javascript:alert(1)','visible'=>true,'order'=>1]];$safe=SiteDashboard::normalize([$raw],'https://example.test');ok($safe[0]['title']==='Revenue');ok($safe[0]['value']==='$25.50');ok($safe[0]['url']===null);
$raw['mobile']['url']='https://evil.test/admin';ok(SiteDashboard::normalize([$raw],'https://example.test')[0]['url']===null);
$raw['mobile']['visible']=false;ok(SiteDashboard::normalize([$raw],'https://example.test')===[]);
echo "Passed $n dynamic registration, live data, order, visibility, permission, auth restoration and safe JSON checks.\n";
