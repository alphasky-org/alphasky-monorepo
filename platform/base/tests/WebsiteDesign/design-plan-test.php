<?php
require $argv[1].'/platform/base/src/Supports/WebsiteDesign/DesignPlan.php';
use Alphasky\Base\Supports\WebsiteDesign\DesignPlan as Plan;
$count=0;
function check($value){global $count;if(!$value)throw new RuntimeException('Assertion failed');$count++;}
function rejects(callable $call,string $code){try{$call();throw new LogicException('Expected rejection');}catch(RuntimeException $e){check($e->getMessage()===$code);}}
$p=Plan::draft('site-a','owner-a');
$step=['id'=>'home','title'=>'Home','requirements'=>'Editable hero and contact details','depends_on'=>[]];
rejects(fn()=>Plan::assertExecutable($p,'site-a','owner-a'),'design_approval_required');
rejects(fn()=>Plan::revise($p,'other',0,[],[$step]),'design_access_denied');
rejects(fn()=>Plan::revise($p,'owner-a',1,[],[$step]),'design_revision_conflict');
rejects(fn()=>Plan::revise($p,'owner-a',0,[],[$step,$step]),'invalid_design_step');
$bad=$step;$bad['depends_on']=['home'];
rejects(fn()=>Plan::revise($p,'owner-a',0,[],[$bad]),'invalid_design_dependency');
$p=Plan::revise($p,'owner-a',0,['business'=>'Clinic'],[$step]);
check($p['revision']===1&&$p['status']==='review');
$p=Plan::approve($p,'owner-a',1);Plan::assertExecutable($p,'site-a','owner-a');check(true);
check(Plan::approve($p,'owner-a',1)===$p);
rejects(fn()=>Plan::assertExecutable($p,'site-b','owner-a'),'design_approval_required');
rejects(fn()=>Plan::assertExecutable($p,'site-a','new-owner'),'design_access_denied');
$changed=$p;$changed['brief']['business']='Shop';
rejects(fn()=>Plan::assertExecutable($changed,'site-a','owner-a'),'design_approval_required');
$changed=$p;$changed['steps'][0]['requirements']='Other request';
rejects(fn()=>Plan::assertExecutable($changed,'site-a','owner-a'),'design_approval_required');
$p=Plan::revise($p,'owner-a',1,['business'=>'Shop'],[$step]);
check($p['approval']===null&&$p['revision']===2);
rejects(fn()=>Plan::approve($p,'owner-a',1),'design_revision_conflict');
rejects(fn()=>Plan::assertExecutable($p,'site-a','owner-a'),'design_approval_required');
echo "Passed $count plan ownership/revision/approval assertions.\n";
