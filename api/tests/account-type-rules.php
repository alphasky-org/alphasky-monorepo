<?php
// Run against an installed Laravel application: php api/tests/account-type-rules.php /path/to/site
$root=realpath($argv[1]??'');
if(!$root || !is_file($root.'/bootstrap/app.php')) throw new RuntimeException('Pass the installed Laravel application root.');
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach([true,false] as $required){
 config(['packages.api.api.account_type_required'=>$required]);
 foreach([Alphasky\Api\Http\Requests\LoginRequest::class,Alphasky\Api\Http\Requests\RegisterRequest::class] as $class){
  $rule=(new $class())->rules()['account_type'];
  foreach([[] ,['account_type'=>'individual'],['account_type'=>'company'],['account_type'=>'admin']] as $data){
   $v=validator($data,['account_type'=>$rule]);$expected=isset($data['account_type'])?in_array($data['account_type'],['individual','company']):!$required;
   if($v->passes()!==$expected)throw new RuntimeException('Account type validation regression');
  }
 }
}
echo "16 account-type validation cases passed.\n";
