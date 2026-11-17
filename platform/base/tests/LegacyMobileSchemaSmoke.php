<?php
require $argv[1];
use Alphasky\Base\Supports\WebsiteDesign\LegacyMobileSchema as L;
function ok($value){if(!$value)throw new RuntimeException('Legacy assertion failed');}
$file=L::field('document','Alphasky\\Base\\Forms\\Fields\\MediaFileField',['label'=>'Document']);ok($file['type']==='file');
$repeater=L::field('rows','repeater',['fields'=>[['type'=>'text','label'=>'Title','attributes'=>['name'=>'title']]]]);ok($repeater['fields'][0]['name']==='title');
ok(L::field('api_token','text',[])===null);ok(L::field('password','password',[])['write_only']);ok(L::field('items','multiselect',[])['multiple']);ok(L::field('unknown','new_custom_field',[])===null);echo "OK legacy field recovery and secret protection\n";
