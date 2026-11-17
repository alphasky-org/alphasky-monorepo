<?php
namespace Alphasky\Base\Supports\WebsiteDesign;
use InvalidArgumentException;

/** Validates the data-only mobile wire contract before invoking a generated FormRequest. */
final class ModuleFormValues
{
 public const UPLOAD_TYPES=['file','files','media_file','image','images','audio','signature','sign'];
 public static function read(array $field,mixed $value):mixed {
  if($field['write_only']??false)return '';
  if(is_string($value)&&in_array($field['type'],['repeater','formbuilder','option_attribute_matrix','multiselect','checkbox','multi_check_list','tag','images','files'],true)){$decoded=json_decode($value,true);if(is_array($decoded))$value=$decoded;}
  if($field['type']==='repeater'&&is_array($value)){
   return array_map(function($row)use($field){if(!is_array($row))return [];if(array_is_list($row))$row=array_column($row,'value','key');$result=[];foreach($field['fields']??[] as $child)$result[$child['name']]=self::read($child,$row[$child['name']]??null);return $result;},$value);
  }
  return $value;
 }
 public static function write(array $fields,array $values,callable $upload,bool $update=false,int $depth=0):array {
  if($depth>8||count($values)>200)throw new InvalidArgumentException('invalid_module_fields');
  $known=array_column($fields,null,'name');if(array_diff(array_keys($values),array_keys($known)))throw new InvalidArgumentException('invalid_module_fields');$result=[];
  foreach($known as $name=>$field){
   $value=$values[$name]??null;$type=$field['type'];
   if(($field['write_only']??false)&&$update&&($value===null||$value===''))continue;
   if(!array_key_exists($name,$values)){if(($field['required']??false)&&!$update)throw new InvalidArgumentException('invalid_module_fields');continue;}
   if(($field['required']??false)&&($value===null||$value===''||$value===[]))throw new InvalidArgumentException('invalid_module_fields');
   if(is_array($value)&&isset($value['__upload'])){if(!in_array($type,self::UPLOAD_TYPES,true))throw new InvalidArgumentException('invalid_module_fields');$result[$name]=$upload($value,$type);continue;}
   if($type==='repeater'){
    if(!is_array($value)||!array_is_list($value)||count($value)>min(100,(int)($field['max_items']??50)))throw new InvalidArgumentException('invalid_module_fields');
    $result[$name]=array_map(function($row)use($field,$upload,$depth){if(!is_array($row))throw new InvalidArgumentException('invalid_module_fields');$row=self::write($field['fields']??[],$row,$upload,false,$depth+1);$pairs=[];foreach($row as $key=>$value)$pairs[]=['key'=>$key,'value'=>$value];return $pairs;},$value);continue;
   }
   if(($field['multiple']??false)||in_array($type,['multiselect','files'],true)){
    if(!is_array($value)||!array_is_list($value)||count($value)>200)throw new InvalidArgumentException('invalid_module_fields');
    $single=$field;$single['multiple']=false;if($type==='images')$single['type']='image';elseif($type==='files')$single['type']='file';else $single['type']='select';
    $result[$name]=array_map(fn($item)=>self::write([$single],[$name=>$item],$upload,false,$depth+1)[$name],$value);continue;
   }
   if($type==='option_attribute_matrix'&&isset($field['matrix_fields'])){if(!is_array($value))throw new InvalidArgumentException('invalid_module_fields');$result[$name]=self::write($field['matrix_fields'],$value,$upload,false,$depth+1);continue;}
   if(in_array($type,['formbuilder','option_attribute_matrix'],true)){
    if($value!==null&&!is_array($value))throw new InvalidArgumentException('invalid_module_fields');
    if(strlen(json_encode($value,JSON_THROW_ON_ERROR))>100000)throw new InvalidArgumentException('invalid_module_fields');
    self::jsonData($value,$depth);$result[$name]=$value;continue;
   }
   if(!is_scalar($value)&&$value!==null||is_string($value)&&strlen($value)>200000)throw new InvalidArgumentException('invalid_module_fields');
   $options=$field['options']??[];
   if($options&&$value!==''&&$value!==null&&!in_array((string)$value,array_map('strval',array_column($options,'value')),true))throw new InvalidArgumentException('invalid_module_fields');
   if(($field['runtime_options']??false)&&!$options&&$value!==''&&$value!==null)throw new InvalidArgumentException('invalid_module_fields');
   if($value!==null&&$value!==''){
    if(in_array($type,['number','stars'],true)&&(!is_numeric($value)||(isset($field['min'])&&$value<$field['min'])||(isset($field['max'])&&$value>$field['max'])))throw new InvalidArgumentException('invalid_module_fields');
    if($type==='email'&&!filter_var($value,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('invalid_module_fields');
    if($type==='color'&&!preg_match('/^#[a-f0-9]{6}$/iD',(string)$value))throw new InvalidArgumentException('invalid_module_fields');
    if($type==='gps'){$parts=explode(',',(string)$value);if(count($parts)!==2||!is_numeric($parts[0])||!is_numeric($parts[1])||abs((float)$parts[0])>90||abs((float)$parts[1])>180)throw new InvalidArgumentException('invalid_module_fields');}
   }
   $result[$name]=$value;
  }
  return $result;
 }
 private static function jsonData(mixed $value,int $depth):void {
  if($depth>12)throw new InvalidArgumentException('invalid_module_fields');
  if(is_array($value)){if(isset($value['__upload'])||count($value)>200)throw new InvalidArgumentException('invalid_module_fields');foreach($value as $child)self::jsonData($child,$depth+1);}
  elseif(!is_scalar($value)&&$value!==null)throw new InvalidArgumentException('invalid_module_fields');
 }
}
