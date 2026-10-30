<?php
require $argv[1].'/vendor/autoload.php';$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require_once $argv[2].'/platform/base/src/Supports/WebsiteDesign/DesignCatalogInstaller.php';
use Alphasky\Base\Supports\WebsiteDesign\DesignCatalogInstaller;
$dir=sys_get_temp_dir().'/catalog-installer-'.bin2hex(random_bytes(6));mkdir($dir,0700);$oldBase=$app->basePath();$oldStorage=storage_path();$app->setBasePath($dir);$app->useStoragePath($dir.'/storage');$n=0;
function ok($v){global$n;if(!$v)throw new RuntimeException('Assertion failed');$n++;}
function rejects($f,$message){try{$f();throw new LogicException('Expected failure');}catch(RuntimeException $e){ok($e->getMessage()===$message);}}
function archive($name,$requires=[],$entry=null){global$dir;$file=$dir.'/'.bin2hex(random_bytes(5)).'.zip';$z=new ZipArchive();$z->open($file,ZipArchive::CREATE);$z->addFromString('plugin.json',json_encode(['id'=>'alphasky/'.$name,'version'=>'1.0.0','require'=>$requires]));$z->addFromString($entry??'src/Test.php','<?php');$z->close();return file_get_contents($file);}
function packageFor($name,$bytes,$requires=[]){return ['name'=>$name,'version'=>'1.0.0','requires'=>$requires,'minimum_core_version'=>'0','sha256'=>hash('sha256',$bytes),'path'=>'/api/v1/website-design/dependency/01234567-0123-4123-8123-012345678901/'.$name];}
$installer=new class extends DesignCatalogInstaller {
 public array $enabled=[];public array $calls=[];public bool $fail=false;
 protected function active():array{return $this->enabled;}
 protected function command(array $args):array{$this->calls[]=$args;if($this->fail)throw new RuntimeException('catalog_activation_failed');if($args[0]==='cms:plugin:activate')$this->enabled[]=$args[1];return [];}
};
try{
 $bytes=archive('member');$p=packageFor('member',$bytes);$installer->install([$p],fn()=>$bytes);ok($installer->enabled===['member']);ok(is_file($dir.'/platform/plugins/member/plugin.json'));
 $before=count($installer->calls);$installer->install([$p],function(){throw new LogicException('Already installed package downloaded');});ok(count($installer->calls)===$before);
 $bad=packageFor('blog','bad');rejects(fn()=>$installer->install([$bad],fn()=>'different'),'catalog_hash_mismatch');
 $badBytes=archive('blog',[],'../escape.php');$bad=packageFor('blog',$badBytes);rejects(fn()=>$installer->install([$bad],fn()=>$badBytes),'unsafe_catalog_archive');ok(!file_exists($dir.'/escape.php'));
 $advanced=archive('language-advanced',['alphasky/language']);$pAdvanced=packageFor('language-advanced',$advanced,['alphasky/language']);rejects(fn()=>$installer->install([$pAdvanced],fn()=>$advanced),'catalog_dependency_inactive');
 $language=archive('language');$pLanguage=packageFor('language',$language);$installer->install([$pLanguage,$pAdvanced],fn($path)=>str_ends_with($path,'/language')?$language:$advanced);ok(in_array('language-advanced',$installer->enabled,true));
 $bad=packageFor('blog',archive('member'));$wrong=archive('member');$bad['sha256']=hash('sha256',$wrong);rejects(fn()=>$installer->install([$bad],fn()=>$wrong),'catalog_manifest_mismatch');
 $bad['path']='https://evil.example/file';rejects(fn()=>$installer->install([$bad],fn()=>''),'invalid_catalog_release');
 $backup=archive('backup');$pBackup=packageFor('backup',$backup);$installer->fail=true;rejects(fn()=>$installer->install([$pBackup],fn()=>$backup),'catalog_activation_failed');$installer->fail=false;
 rejects(fn()=>$installer->install([$pBackup],fn()=>$backup),'catalog_install_requires_reconciliation');
 ok(!in_array('backup',$installer->enabled,true));
 $new=$p;$new['version']='99.0.0';rejects(fn()=>$installer->install([$new],fn()=>''),'catalog_existing_plugin_needs_update');
 echo "Passed $n catalog archive, activation, order, replay and failure checks.\n";
}finally{$app->setBasePath($oldBase);$app->useStoragePath($oldStorage);(new Illuminate\Filesystem\Filesystem())->deleteDirectory($dir);}
