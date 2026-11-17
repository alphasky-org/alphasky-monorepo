<?php
namespace Alphasky\Base\Supports\WebsiteDesign;
/** Upgrade legacy metadata in memory from the installed native form; never rewrite customer JSON. */
final class LegacyMobileSchema {
 private const TYPES=['text'=>'text','textarea'=>'textarea','number'=>'number','email'=>'email','url'=>'url','tel'=>'tel','phone'=>'tel','phonenumber'=>'tel','date'=>'date','datepicker'=>'date','time'=>'time','timepicker'=>'time','datetime'=>'datetime-local','hidden'=>'hidden','password'=>'password','select'=>'select','select2'=>'select','radio'=>'radio','multiselect'=>'multiselect','multiselect2'=>'multiselect','multichecklist'=>'multiselect','onoff'=>'checkbox','onoffcheckbox'=>'checkbox','checkbox'=>'checkbox','editor'=>'editor','codeeditor'=>'code_editor','code'=>'code','file'=>'file','mediafile'=>'file','image'=>'image','mediaimage'=>'image','images'=>'images','mediaimages'=>'images','audio'=>'audio','signature'=>'signature','sign'=>'sign','repeater'=>'repeater','tag'=>'tag','tags'=>'tag','color'=>'color','colorselector'=>'color','formbuilder'=>'formbuilder','icon'=>'icon','stars'=>'stars','rating'=>'stars','gps'=>'gps','googlefonts'=>'google_fonts','uiselector'=>'ui_selector','tree'=>'tree','category'=>'category','autocomplete'=>'autocomplete'];
 public static function field(string $name,string $type,array $options):?array {
  $type=preg_replace('/Field$/','',basename(str_replace('\\','/',$type)));$key=strtolower(preg_replace('/[^a-zA-Z]/','',$type));$type=self::TYPES[$key]??null;
  if(!$type||!preg_match('/^[a-z][a-z0-9_]{0,100}$/D',$name)||$type!=='password'&&preg_match('/password|secret|token|api_key|credential/i',$name))return null;
  $fields=[];foreach($options['fields']??[] as $child){$attributes=$child['attributes']??[];$field=self::field($child['name']??$attributes['name']??'',(string)($child['type']??'text'),$child+$attributes);if(!$field)return null;$fields[]=$field;}
  $multiple=in_array($type,['multiselect','images','tag'],true)||($type==='checkbox'&&!empty($options['choices']));
  return ['name'=>$name,'type'=>$type,'label_en'=>strip_tags((string)($options['label']??$name)),'label_ar'=>strip_tags((string)($options['label']??$name)),'required'=>(bool)($options['required']??$options['attr']['required']??false),'default'=>$type==='password'?'':($options['default_value']??''),'write_only'=>$type==='password','multiple'=>$multiple,'table'=>false,'fields'=>$fields,'options'=>[],'runtime_options'=>in_array($type,['select','multiselect','radio','tree','category','autocomplete'],true)||($type==='checkbox'&&$multiple)];
 }
 public static function upgrade(string $plugin,array $section):array {
  $class='Alphasky\\'.ucfirst($plugin).'\\Forms\\'.ucfirst($section['key']).'Form';if(!class_exists($class))return $section;
  try{$form=$class::create();$native=$form->getFields();}catch(\Throwable){return $section;}
  $fields=array_column($section['fields'],null,'name');$missing=$section['unsupported_fields']??[];$unresolved=[];
  foreach(array_unique([...array_keys($fields),...$missing]) as $name){
   if(!isset($native[$name])){if(in_array($name,$missing,true))$unresolved[]=$name;continue;}
   $field=self::field($name,$native[$name]->getType(),$native[$name]->getOptions());if(!$field){if(in_array($name,$missing,true))$unresolved[]=$name;continue;}
   $field['virtual']=!(new $section['model']())->isFillable($name);
   foreach(['label_ar','label_en','default','table'] as $key)if(array_key_exists($key,$fields[$name]??[]))$field[$key]=$fields[$name][$key];
   $fields[$name]=array_replace($fields[$name]??[],$field);
  }
  $section['fields']=array_values($fields);$section['unsupported_fields']=$unresolved;
  if($missing&&!$unresolved)$section['actions']=['list'=>true,'create'=>true,'update'=>true,'delete'=>true];
  return $section;
 }
}
