<?php
namespace Alphasky\Base\Supports\WebsiteDesign;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;
/** Invoked by the trusted site broker as the tenant Unix user, never by browser-supplied paths. */
final class DesignSiteClient {
 public function __construct(private array $connection) {
  $url=$connection['server']??'';
  if(!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_USER)||parse_url($url,PHP_URL_QUERY)||parse_url($url,PHP_URL_FRAGMENT)||empty($connection['token'])||empty($connection['gateway'])||empty($connection['domain']))throw new RuntimeException('invalid_connection');
 }
 private function http(){return Http::connectTimeout(15)->timeout(1200)->withoutRedirecting()->withHeaders(['X-API-KEY'=>$this->connection['gateway'],'X-Design-Admission'=>$this->connection['admission']??'','X-Design-Time'=>$this->connection['admission_time']??''])->acceptJson();}
 public function request(array $data):array {
  $r=$this->http()->post(rtrim($this->connection['server'],'/').'/api/v1/website-design',array_merge($data,['domain'=>$this->connection['domain'],'alphasky_key'=>$this->connection['token']]));
  if(!$r->successful()||!is_array($r->json('data')))throw new RuntimeException('central_design_unavailable');return $r->json('data');
 }
 public function run(array $request):array {
  $operation=$this->request($request);
  if(($operation['status']??'')!=='artifact_ready')return $operation;
  $result=$operation['result'];
  if(isset($result['brand'])){(new DesignPublisher())->brand($request['brand']);return $this->request(['action'=>'report','operation_id'=>$operation['id'],'success'=>true]);}
  $package=$result['package'];
  try{
   $this->install($result['module_name'],$package);
   if(!empty($result['homepage_shortcode']))(new DesignPublisher())->homepage($result['module_name'],$result['homepage_shortcode']);
   $check=Http::connectTimeout(10)->timeout(30)->withoutRedirecting()->get(rtrim((string)config('app.url'),'/').'/');
   if(!$check->successful()||(!empty($result['homepage_shortcode'])&&!str_contains($check->body(),'data-design-home')))throw new RuntimeException('site_verification_failed');
   return $this->request(['action'=>'report','operation_id'=>$operation['id'],'success'=>true]);
  }catch(\Throwable $e){report($e);return $this->request(['action'=>'report','operation_id'=>$operation['id'],'success'=>false]);}
 }
 private function preparePluginDirectory():void {
  foreach([base_path('platform'),base_path('platform/plugins')]as$directory){if(is_link($directory))throw new RuntimeException('unsafe_plugin_directory');if(!is_dir($directory)&&!mkdir($directory,0755))throw new RuntimeException('plugin_directory_unavailable');}
 }
 public function install(string $module,array $package):void {
  if(!preg_match('/^design_[a-f0-9]{12}_[a-z][a-z0-9_]{0,39}$/D',$module)||!preg_match('/^[a-f0-9]{64}$/D',$package['sha256']??'')||!preg_match('#^/api/v1/plugin-package/[0-9]+\?[^\r\n]+$#D',$package['path']??''))throw new RuntimeException('invalid_release');
  $root=storage_path('app/private/website-design');if(!is_dir($root))mkdir($root,0700,true);
  $lock=fopen($root.'/install.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('install_locked');
  try{
   $receipt=$root.'/'.$module.'.json';$saved=is_file($receipt)?json_decode(file_get_contents($receipt),true):[];
   if(($saved['status']??'')==='applying')throw new RuntimeException('install_requires_reconciliation');
   if(($saved['sha256']??'')===$package['sha256']&&($saved['status']??'')==='installed')return;
   $response=$this->http()->post(rtrim($this->connection['server'],'/').$package['path'],['domain'=>$this->connection['domain'],'alphasky_key'=>$this->connection['token']]);
   $bytes=$response->body();if(!$response->successful()||strlen($bytes)>25000000||!hash_equals($package['sha256'],hash('sha256',$bytes)))throw new RuntimeException('release_hash_mismatch');
   $zipPath=$root.'/'.$package['sha256'].'.zip';file_put_contents($zipPath,$bytes,LOCK_EX);chmod($zipPath,0600);
   $zip=new \ZipArchive();if($zip->open($zipPath)!==true)throw new RuntimeException('invalid_archive');
   $total=0;for($i=0;$i<$zip->numFiles;$i++){$entry=$zip->statIndex($i);$name=str_replace('\\','/',$entry['name']);$zip->getExternalAttributesIndex($i,$os,$attr);$total+=$entry['size'];if($total>100000000||$zip->numFiles>5000||str_starts_with($name,'/')||preg_match('#(^|/)\.\.?(/|$)|^[a-z]:|\x00#i',$name)||(($attr>>16)&0170000)===0120000)throw new RuntimeException('unsafe_archive');}
   $this->preparePluginDirectory();
   $target=base_path('platform/plugins/'.$module);if(is_link($target))throw new RuntimeException('unsafe_target');
   $stage=$root.'/stage-'.bin2hex(random_bytes(8));mkdir($stage,0700);if(!$zip->extractTo($stage))throw new RuntimeException('extract_failed');$zip->close();
   $plugin=json_decode((string)@file_get_contents($stage.'/plugin.json'),true);if(!is_array($plugin))throw new RuntimeException('invalid_plugin');
   $previousPlugins=get_active_plugins();$backup=is_dir($target)?$root.'/backup-'.$module.'-'.date('YmdHis').'-'.bin2hex(random_bytes(4)):null;
   file_put_contents($receipt,json_encode(['status'=>'applying','sha256'=>$package['sha256'],'backup'=>$backup]),LOCK_EX);
   $movedOld=false;$movedNew=false;
   if($backup){if(!rename($target,$backup))throw new RuntimeException('backup_failed');$movedOld=true;}
   chmod($stage,0755);if(!rename($stage,$target))throw new RuntimeException('install_move_failed');$movedNew=true;
   foreach([['cms:plugin:activate',$module],['migrate','--force','--path=platform/plugins/'.$module.'/database/migrations'],['cms:publish:assets']]as$args){if($args[0]==='migrate'&&!is_dir($target.'/database/migrations'))continue;$p=new Process(array_merge([PHP_BINARY,base_path('artisan')],$args),base_path(),null,null,300);$p->run();if(!$p->isSuccessful())throw new RuntimeException('plugin_apply_failed');}
   file_put_contents($receipt,json_encode(['status'=>'installed','sha256'=>$package['sha256'],'release_id'=>$package['release_id']??null]),LOCK_EX);unlink($zipPath);
  }catch(\Throwable $error){
   if(isset($previousPlugins,$target)){if(($movedNew??false)&&is_dir($target))rename($target,$root.'/failed-'.$module.'-'.bin2hex(random_bytes(8)));if(($movedOld??false)&&$backup&&is_dir($backup))rename($backup,$target);\Alphasky\Setting\Facades\Setting::forceSet('activated_plugins',json_encode($previousPlugins))->save();app(\Alphasky\PluginManagement\Services\PluginService::class)->clearCache();file_put_contents($receipt,json_encode(['status'=>'failed','sha256'=>$package['sha256'],'backup'=>$backup]),LOCK_EX);}
   throw $error;
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}
