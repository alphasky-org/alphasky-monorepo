<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use RuntimeException;

final class SiteModules
{
 public function catalog(): array {
  $modules=[];
  foreach(get_active_plugins() as $plugin){
   if(!is_string($plugin)||!preg_match('/^[a-z][a-z0-9_]{0,100}$/D',$plugin))continue;
   $path=plugin_path($plugin.'/config/mobile-admin.json');
   if(!is_file($path)||is_link($path)||filesize($path)>500000)continue;
   $schema=json_decode(file_get_contents($path),true);
   if(!in_array($schema['version']??null,[1,2],true)||($schema['module']??'')!==$plugin)continue;
   $sections=[];
   foreach($schema['sections']??[] as $section){
    if(!$this->validSection($plugin,$section))continue;
    if($schema['version']===1)$section=LegacyMobileSchema::upgrade($plugin,$section);
    $section=$this->hydrate($plugin,$section);unset($section['model'],$section['controller']);$sections[]=$section;
   }
   if($sections)$modules[]=['module'=>$plugin,'label_ar'=>$schema['label_ar'],'label_en'=>$schema['label_en'],'sections'=>$sections];
  }
  return ['modules'=>$modules];
 }
 private function hydrate(string $plugin,array $section,$row=null,array $values=[]):array {
  $class='Alphasky\\'.ucfirst($plugin).'\\Forms\\'.ucfirst($section['key']).'Form';
  $form=null;
  if($values)request()->merge(array_intersect_key($values,array_column($section['fields'],null,'name')));
  foreach($section['fields'] as &$field){
   if(!($field['runtime_options']??false)&&!($field['virtual']??false))continue;
   try{if(!$form&&class_exists($class))$form=$row?$class::createFromModel($row):$class::create();
    if($field['type']==='option_attribute_matrix'){
     $field['matrix_fields']=[];foreach($form?->getFields()??[] as $fieldName=>$control){if(preg_match('/^'.preg_quote($field['name'],'/').'\\[([^\\]]+)\\]$/D',$fieldName,$match)){
      $options=[];foreach($control->getOption('choices',[]) as $value=>$label)if(is_scalar($label))$options[]=['value'=>(string)$value,'label_en'=>(string)$label,'label_ar'=>(string)$label];
      $field['matrix_fields'][]=['name'=>$match[1],'label_en'=>$control->getOption('label',$match[1]),'label_ar'=>$control->getOption('label',$match[1]),'type'=>'select','required'=>true,'options'=>$options,'default'=>$control->getValue()];
     }}continue;
    }
    if($row&&($field['virtual']??false))$field['current_value']=$form?->getField($field['name'])->getValue();
    $choices=$form?->getField($field['name'])->getOption('choices',[])??[];
    if($choices instanceof \Illuminate\Contracts\Support\Arrayable)$choices=$choices->toArray();
    if(!is_array($choices))throw new RuntimeException('module_options_unavailable');
    $options=[];foreach($choices as $key=>$value){if(is_scalar($value))$options[]=['value'=>(string)$key,'label_en'=>(string)$value,'label_ar'=>(string)$value];elseif(is_array($value))foreach($value as $nestedKey=>$nestedValue)if(is_scalar($nestedValue))$options[]=['value'=>(string)$nestedKey,'label_en'=>(string)$nestedValue,'label_ar'=>(string)$nestedValue];}
    $field['options']=$options;
   }catch(\Throwable){$section['unsupported_fields'][]=$field['name'];$section['actions']['create']=false;$section['actions']['update']=false;}
  }unset($field);return $section;
 }
 private function validSection(string $plugin,array $section): bool {
  $key=$section['key']??'';
  if(!preg_match('/^[a-z][a-z0-9_]{0,80}$/D',$key))return false;
  $prefix='Alphasky\\'.ucfirst($plugin).'\\';
  return ($section['model']??'')===$prefix.'Models\\'.ucfirst($key)&&($section['controller']??'')===$prefix.'Http\\Controllers\\'.ucfirst($key).'Controller'
   &&is_subclass_of($section['model'],\Illuminate\Database\Eloquent\Model::class)&&class_exists($section['controller']);
 }
 private function schema(array $input): array {
  $module=$input['module']??'';$key=$input['section']??'';
  foreach($this->catalog()['modules'] as $schema)if($schema['module']===$module)foreach($schema['sections'] as $section)if($section['key']===$key){
   $prefix='Alphasky\\'.ucfirst($module).'\\';return $section+['module'=>$module,'model'=>$prefix.'Models\\'.ucfirst($key),'controller'=>$prefix.'Http\\Controllers\\'.ucfirst($key).'Controller'];
  }
  throw new RuntimeException('module_unavailable');
 }
 private function revision($row): string { return hash('sha256',json_encode($row->getRawOriginal(),JSON_THROW_ON_ERROR)); }
 private function record($row,array $schema): array {
  $schema=$this->hydrate($schema['module'],$schema,$row);
  $values=[];foreach($schema['fields'] as $field){$value=($field['virtual']??false)?($field['current_value']??null):$row->getAttribute($field['name']);if($field['type']==='option_attribute_matrix')$value=array_column($field['matrix_fields']??[],'default','name');if($value instanceof \BackedEnum)$value=$value->value;elseif($value instanceof \JsonSerializable)$value=$value->jsonSerialize();$values[$field['name']]=$this->previewValue($field,ModuleFormValues::read($field,$value));}

  return ['id'=>$row->getKey(),'values'=>$values,'version'=>$this->revision($row),'fields'=>$schema['fields']];
 }
 private function previewValue(array $field,mixed $value):mixed {
  if(in_array($field['type'],ModuleFormValues::UPLOAD_TYPES,true)){if(is_array($value))return array_map(fn($item)=>is_string($item)?\RvMedia::getImageUrl($item):$item,$value);return is_string($value)?\RvMedia::getImageUrl($value):$value;}
  if($field['type']==='repeater'&&is_array($value))return array_map(function($row)use($field){foreach($field['fields']??[] as $child)if(array_key_exists($child['name'],$row))$row[$child['name']]=$this->previewValue($child,$row[$child['name']]);return $row;},$value);
  return $value;
 }
 public function execute(array $input): array {
  $admin=\Alphasky\ACL\Models\User::query()->where('super_user',1)->orderBy('id')->first();if(!$admin)throw new RuntimeException('site_admin_unavailable');Auth::guard('web')->setUser($admin);
  if($input['action']==='modules')return $this->catalog();
  $schema=$this->schema($input);$class=$schema['model'];$model=new $class();
  foreach($schema['fields'] as $field)if(!($field['virtual']??false)&&!$model->isFillable($field['name']))throw new RuntimeException('module_schema_changed');
  if($input['action']==='module_form'){$row=isset($input['id'])?$class::query()->findOrFail($input['id']):null;$schema=$this->hydrate($schema['module'],$schema,$row,$input['values']??[]);return ['fields'=>$schema['fields'],'unsupported_fields'=>$schema['unsupported_fields']];}
  if($input['action']==='module_rows'){
   $page=max(1,min(100000,(int)($input['page']??1)));$query=$class::query();
   return ['total'=>$query->count(),'page'=>$page,'rows'=>$query->orderByDesc($model->getKeyName())->skip(($page-1)*25)->take(25)->get()->map(fn($row)=>$this->record($row,$schema))->all()];
  }
  $action=match($input['action']){'module_create'=>'create','module_update'=>'update','module_delete'=>'delete',default=>throw new RuntimeException('unsupported_action')};
  if(!($schema['actions'][$action]??false))throw new RuntimeException('module_form_unsupported');
  Validator::make($input,['request_id'=>'required|uuid','id'=>$action==='create'?'prohibited':'required|integer|min:1','version'=>$action==='create'?'prohibited':'required|string|size:64','values'=>$action==='delete'?'prohibited':'present|array','confirmed'=>$action==='delete'?'required|accepted':'sometimes|boolean'])->validate();
  if($action!=='delete')$schema=$this->hydrate($schema['module'],$schema,$action==='update'?$class::query()->find($input['id']):null,$input['values']);
  if($action!=='delete')ModuleFormValues::write($schema['fields'],$input['values'],static function($value,$type){if(!is_array($value)||($value['__upload']??false)!==true)throw new RuntimeException('invalid_module_fields');return '__pending_upload__';},$action==='update');
  $dir=storage_path('app/private/site-control/operations');if(!is_dir($dir))mkdir($dir,0700,true);
  $path=$dir.'/'.$input['request_id'].'.json';$lock=fopen($dir.'/writes.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('module_busy');
  try{
   $hash=hash('sha256',json_encode($input,JSON_THROW_ON_ERROR));
   if(is_file($path)){$previous=json_decode(file_get_contents($path),true);if(($previous['hash']??'')!==$hash)throw new RuntimeException('request_conflict');if(isset($previous['result']))return $previous['result'];throw new RuntimeException('module_operation_unknown');}
   $admin=\Alphasky\ACL\Models\User::query()->where('super_user',1)->orderBy('id')->first();if(!$admin)throw new RuntimeException('site_admin_unavailable');
   Auth::guard('web')->setUser($admin);
   $uploads=new ModuleFormUploads();
   try{$result=DB::transaction(function()use($input,$action,$schema,$class,$admin,$path,$hash,$uploads){
    $row=$action==='create'?null:$class::query()->lockForUpdate()->find($input['id']);
    if($action!=='create'&&(!$row||!hash_equals($this->revision($row),$input['version'])))throw new RuntimeException('record_changed');
    $values=$input['values']??[];
    if($action!=='delete')$values=ModuleFormValues::write($schema['fields'],$values,$uploads,$action==='update');
    $method=match($action){'create'=>'store','update'=>'update','delete'=>'destroy'};
    $base=Request::create('/admin/'.$schema['module'].'/'.$schema['key'], $action==='create'?'POST':($action==='delete'?'DELETE':'PUT'),$values);
    $base->headers->set('Accept','application/json');$base->setUserResolver(fn()=>$admin);
    $route=new \Illuminate\Routing\Route([$base->method()],$base->path(),fn()=>null);$route->name($schema['module'].'.'.$schema['key'].'.'.$method);$route->bind($base);
    if($row)$route->setParameter($schema['key'],$row);
    $base->setRouteResolver(fn()=>$route);app()->instance('request',$base);
    $controller=app($schema['controller']);$reflection=new \ReflectionMethod($controller,$method);$arguments=[];
    foreach($reflection->getParameters() as $param){
     $type=$param->getType();$type=$type instanceof \ReflectionNamedType?$type->getName():'';
     if($type===$class){$arguments[]=$row??new $class();$route->setParameter($param->getName(),$row);}
     elseif(is_a($type,\Illuminate\Foundation\Http\FormRequest::class,true)){
      $request=$type::createFrom($base);$request->setContainer(app())->setRedirector(app('redirect'));$request->setUserResolver(fn()=>$admin);$request->setRouteResolver(fn()=>$route);$request->validateResolved();$arguments[]=$request;app()->instance('request',$request);
     }elseif($type===Request::class){$arguments[]=$base;}elseif($type===\Alphasky\Base\Http\Responses\BaseHttpResponse::class){$arguments[]=app($type);}else throw new RuntimeException('module_form_unsupported');
    }
    // A persisted pending receipt prevents a blind retry after a disconnected/ambiguous write.
    file_put_contents($path,json_encode(['hash'=>$hash,'status'=>'pending']),LOCK_EX);chmod($path,0600);
    $response=$reflection->invokeArgs($controller,$arguments);
    if($response instanceof \Illuminate\Contracts\Support\Responsable)$response=$response->toResponse($base);
    if($response instanceof \Symfony\Component\HttpFoundation\Response){$payload=json_decode($response->getContent(),true);if($response->getStatusCode()>=400||!empty($payload['error']))throw new RuntimeException('module_write_failed');}
    return ['saved'=>true,'action'=>$action];
   });}catch(\Throwable $e){$uploads->rollback();throw $e;}
   file_put_contents($path,json_encode(['hash'=>$hash,'result'=>$result]),LOCK_EX);return $result;
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}
