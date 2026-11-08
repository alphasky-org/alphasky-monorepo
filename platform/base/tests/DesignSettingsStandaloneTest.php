<?php
$host=$argv[1];require $host.'/vendor/autoload.php';$app=require $host.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require dirname(__DIR__).'/src/Supports/WebsiteDesign/DesignSettings.php';
require $argv[2].'/platform/plugins/survey/src/Workflow/DesignSettingsPlan.php';
use Alphasky\Base\Supports\WebsiteDesign\DesignSettings;use Alphasky\Survey\Workflow\DesignSettingsPlan;
$n=0;function check($ok){global $n;if(!$ok)throw new RuntimeException('Assertion '.($n+1));$n++;}
function rejects(callable $f){try{$f();throw new LogicException('Expected rejection');}catch(RuntimeException|Illuminate\Validation\ValidationException $e){check(true);}}
$service=new DesignSettings();$planner=new DesignSettingsPlan();$inventory=$service->inventory();$context=$planner->context($inventory);
check(count($context['groups'])===7);check(!array_key_exists('value',$planner->aiContext($context)['groups']['email']['fields']['email_from_name']));check(isset($context['groups']['admin-appearance']['fields']['admin_appearance_locale']));check(!isset($context['groups']['email']['fields']['email_password']));
$plan=$planner->proposal(['version'=>1,'action'=>'settings.update','group'=>'admin-appearance','values'=>['admin_appearance_locale'=>'ar','admin_appearance_locale_direction'=>'rtl']],$context);
check($plan['dependencies']===['language','language-advanced']);check($plan['requires_confirmation']===false);
check($planner->proposal(['version'=>1,'action'=>'backup.create','name'=>'Owner backup','include_media'=>true],$context)['dependencies']===['backup']);
rejects(fn()=>$planner->proposal(['version'=>1,'action'=>'settings.update','group'=>'email','values'=>['email_password'=>'forbidden']],$context));
rejects(fn()=>DesignSettings::validateEnvelope(['version'=>1,'action'=>'backup.restore']));
rejects(fn()=>$planner->proposal(['version'=>1,'action'=>'backup.create','name'=>'Test','include_media'=>true,'confirmed'=>true],$context));
Illuminate\Support\Facades\DB::beginTransaction();
$id=Illuminate\Support\Str::uuid()->toString();
try {
 $key='datatables_default_enable_responsive';$old=$inventory['groups']['datatables']['fields'][$key]['value'];$value=(string)$old==='1'?'0':'1';
 $p=['version'=>1,'action'=>'settings.update','group'=>'datatables','values'=>[$key=>$value],'expected'=>[$key=>$old]];
 $result=$service->apply($id,$p);check($result['verified']===true);check((string)setting($key)===$value);
 check($service->apply($id,$p)===$result);
 $changed=$p;$changed['values'][$key]=$old;rejects(fn()=>$service->apply($id,$changed));
 rejects(fn()=>$service->apply(Illuminate\Support\Str::uuid()->toString(),$p));
 $danger=$planner->proposal(['version'=>1,'action'=>'settings.update','group'=>'email','values'=>['email_from_name'=>'Example']],$context);check($danger['requires_confirmation']===true);
 rejects(fn()=>$service->apply(Illuminate\Support\Str::uuid()->toString(),$danger['plan']));
} finally {Illuminate\Support\Facades\DB::rollBack();@unlink(storage_path('app/private/design-settings/'.$id.'.json'));}
echo "Passed $n settings schema, dependency, secret exclusion, confirmation, mutation, stale-write and idempotency checks.\n";
