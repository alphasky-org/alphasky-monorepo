<?php
require $argv[1];
use Alphasky\Base\Supports\WebsiteDesign\ModuleFormValues as V;
$count=0;
function same($a,$b){global $count;if($a!==$b)throw new RuntimeException('Mismatch: '.var_export([$a,$b],true));$count++;}
function rejects(callable $fn){global $count;try{$fn();}catch(InvalidArgumentException){$count++;return;}throw new RuntimeException('Expected rejection');}
$upload=fn($value,$type)=>'uploads/'.$type.'.png';
$fields=[['name'=>'choices','type'=>'multiselect','multiple'=>true,'options'=>[['value'=>'a'],['value'=>'b']]],['name'=>'active','type'=>'checkbox'],['name'=>'quantity','type'=>'number']];
same(V::write($fields,['choices'=>['a','b'],'active'=>false,'quantity'=>0],$upload),['choices'=>['a','b'],'active'=>false,'quantity'=>0]);
rejects(fn()=>V::write($fields,['choices'=>['unknown']],$upload));rejects(fn()=>V::write($fields,['injected'=>'value'],$upload));
$repeat=['name'=>'items','type'=>'repeater','fields'=>[['name'=>'title','type'=>'text','required'=>true],['name'=>'image','type'=>'image']]];
$values=['items'=>[['title'=>'Hello','image'=>['__upload'=>true]]]];
$written=V::write([$repeat],$values,$upload);same($written['items'][0][0],['key'=>'title','value'=>'Hello']);same(V::read($repeat,json_encode($written['items'])),[['title'=>'Hello','image'=>'uploads/image.png']]);
rejects(fn()=>V::write([$repeat],['items'=>[['title'=>'']]],$upload));rejects(fn()=>V::write([$repeat],['items'=>[['title'=>'x','unknown'=>1]]],$upload));
$password=['name'=>'password','type'=>'password','write_only'=>true,'required'=>true];same(V::read($password,'hash'),'');same(V::write([$password],['password'=>''],$upload,true),[]);rejects(fn()=>V::write([$password],['password'=>''],$upload,false));
rejects(fn()=>V::write([['name'=>'plain','type'=>'text']],['plain'=>['__upload'=>true]],$upload));
$gps=['name'=>'gps','type'=>'gps'];rejects(fn()=>V::write([$gps],['gps'=>'91,0'],$upload));same(V::write([$gps],['gps'=>'31.9,35.9'],$upload),['gps'=>'31.9,35.9']);
$multi=['name'=>'photos','type'=>'images','multiple'=>true];same(V::write([$multi],['photos'=>[['__upload'=>true],'existing.png']],$upload),['photos'=>['uploads/image.png','existing.png']]);
echo "OK $count assertions\n";
