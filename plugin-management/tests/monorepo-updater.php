<?php
$host = rtrim((string) getenv('ALPHASKY_TEST_HOST'), '/');
if (!is_file($host.'/vendor/autoload.php')) throw new RuntimeException('Set ALPHASKY_TEST_HOST to a bootable CMS checkout.');
require $host.'/vendor/autoload.php';
$app=require $host.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require dirname(__DIR__).'/src/Services/AlphaskyMonorepoUpdater.php';
use Symfony\Component\Process\Process;
function git(array $a,string $cwd):string{$p=new Process(array_merge(['git'], $a),$cwd);$p->mustRun();return trim($p->getOutput());}
function ok($b){if(!$b)throw new RuntimeException('Assertion failed');$GLOBALS['n']++;}
$n=0;$base=sys_get_temp_dir().'/alphasky-update-'.bin2hex(random_bytes(5));mkdir($base);mkdir($base.'/origin');$source=$base.'/origin';git(['init','-b','main'],$source);git(['config','user.email','test@example.invalid'],$source);git(['config','user.name','Test'],$source);file_put_contents($source.'/VERSION','1.0.0');file_put_contents($source.'/code.php','old');git(['add','.'],$source);git(['commit','-m','initial'],$source);git(['clone',$source,$base.'/site'],$base);
$updater=new class extends Alphasky\PluginManagement\Services\AlphaskyMonorepoUpdater {public string $path,$repo;public int $checks=0;public string $release='1.3.4';protected function localPath():string{return $this->path;}protected function repositoryUrl():string{return $this->repo;}public function remoteVersion():string{$this->checks++;return $this->release;}};
$updater->path=$base.'/site';$updater->repo=$source;
try{
 file_put_contents($source.'/VERSION','1.3.4');file_put_contents($source.'/code.php','new');git(['add','.'],$source);git(['commit','-m','release'],$source);
 file_put_contents($updater->path.'/custom.php','customer');try{$updater->checkAndUpdate();throw new LogicException('Untracked update accepted');}catch(RuntimeException $e){ok(!$e instanceof LogicException);}ok($updater->checks===0);ok(!$updater->status()['can_update']);unlink($updater->path.'/custom.php');
 file_put_contents($updater->path.'/code.php','customer');try{$updater->checkAndUpdate();throw new LogicException('Dirty update accepted');}catch(RuntimeException $e){ok(!$e instanceof LogicException);}ok(file_get_contents($updater->path.'/code.php')==='customer');file_put_contents($updater->path.'/code.php','old');
 $before=git(['rev-parse','HEAD'],$updater->path);$result=$updater->checkAndUpdate();ok($result['updated']);ok($updater->localVersion()==='1.3.4');ok(git(['rev-parse','refs/alphasky/previous'],$updater->path)===$before);ok(file_get_contents($updater->path.'/code.php')==='new');ok($updater->checkAndUpdate()['reason']==='already_latest');
 $lock=fopen($updater->path.'/.git/alphasky-update.lock','c');flock($lock,LOCK_EX);try{$updater->checkAndUpdate();throw new LogicException('Concurrent update accepted');}catch(RuntimeException $e){ok(str_contains($e->getMessage(),'already running'));}fclose($lock);
 git(['config','user.email','test@example.invalid'],$updater->path);git(['config','user.name','Test'],$updater->path);file_put_contents($updater->path.'/local.php','local');git(['add','.'],$updater->path);git(['commit','-m','local change'],$updater->path);$localHead=git(['rev-parse','HEAD'],$updater->path);
 file_put_contents($source.'/VERSION','1.3.5');git(['add','.'],$source);git(['commit','-m','next release'],$source);$updater->release='1.3.5';try{$updater->checkAndUpdate();throw new LogicException('Diverged history accepted');}catch(RuntimeException $e){ok(!$e instanceof LogicException);}ok(git(['rev-parse','HEAD'],$updater->path)===$localHead);ok(is_file($updater->path.'/local.php'));
 $updater->path=$base.'/missing';ok($updater->status()['blocked_reason']==='not_git_repository');
 echo "Passed $n updater checks (real temporary Git repositories, no external network).\n";
}finally{(new Process(['rm','-rf',$base]))->mustRun();}
