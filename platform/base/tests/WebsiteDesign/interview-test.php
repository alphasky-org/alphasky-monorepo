<?php
require $argv[1].'/platform/base/src/Supports/WebsiteDesign/DesignInterview.php';
$i=new Alphasky\Base\Supports\WebsiteDesign\DesignInterview();
foreach(['introduce','shop','booking','catalogue','content'] as $goal){
 $a=[];$guard=0;
 while(($q=$i->view($a,'en')['question'])!==null){
  if(++$guard>30)throw new RuntimeException('Interview did not terminate');
  $v=$q['id']==='goal'?$goal:($q['multiple']?array_column($q['options'],'value'):($q['options'][0]['value']??'Example project details'));
  $a=$i->answer($a,$q['id'],$v,'en');
 }
 if(!$i->view($a,'ar')['complete'])throw new RuntimeException('Locale changed completion');
 if($goal==='shop'&&!isset($a['payments']))throw new RuntimeException('Missing payments');
 if($goal==='booking'&&!isset($a['reminders']))throw new RuntimeException('Missing reminders');
 if($goal!=='shop'&&isset($a['payments']))throw new RuntimeException('Unrelated payments');
}
echo "Passed five complete interview branches and Arabic/English consistency.\n";
