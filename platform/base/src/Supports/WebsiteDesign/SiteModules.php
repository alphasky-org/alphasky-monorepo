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
   if(($schema['version']??null)!==1||($schema['module']??'')!==$plugin)continue;
   $sections=[];
   foreach($schema['sections']??[] as $section){
    if(!$this->validSection($plugin,$section))continue;
    unset($section['model'],$section['controller']);$sections[]=$section;
   }
   if($sections)$modules[]=['module'=>$plugin,'label_ar'=>$schema['label_ar'],'label_en'=>$schema['label_en'],'sections'=>$sections];
  }
  return ['modules'=>$modules];
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
  $values=[];foreach($schema['fields'] as $field){$value=$row->getAttribute($field['name']);if($value instanceof \BackedEnum)$value=$value->value;elseif($value instanceof \JsonSerializable)$value=$value->jsonSerialize();$values[$field['name']]=is_scalar($value)||$value===null?$value:(string)$row->getRawOriginal($field['name']);}
  return ['id'=>$row->getKey(),'values'=>$values,'version'=>$this->revision($row)];
 }
 public function execute(array $input): array {
  if($input['action']==='modules')return $this->catalog();
  $schema=$this->schema($input);$class=$schema['model'];$model=new $class();
  foreach($schema['fields'] as $field)if(!$model->isFillable($field['name']))throw new RuntimeException('module_schema_changed');
  if($input['action']==='module_rows'){
   $page=max(1,min(100000,(int)($input['page']??1)));$query=$class::query();
   return ['total'=>$query->count(),'page'=>$page,'rows'=>$query->orderByDesc($model->getKeyName())->skip(($page-1)*25)->take(25)->get()->map(fn($row)=>$this->record($row,$schema))->all()];
  }
  $action=match($input['action']){'module_create'=>'create','module_update'=>'update','module_delete'=>'delete',default=>throw new RuntimeException('unsupported_action')};
  if(!($schema['actions'][$action]??false))throw new RuntimeException('module_form_unsupported');
  Validator::make($input,['request_id'=>'required|uuid','id'=>$action==='create'?'prohibited':'required|integer|min:1','version'=>$action==='create'?'prohibited':'required|string|size:64','values'=>$action==='delete'?'prohibited':'present|array','confirmed'=>$action==='delete'?'required|accepted':'sometimes|boolean'])->validate();
  if($action!=='delete'){
   $fields=array_column($schema['fields'],null,'name');
   if(array_diff(array_keys($input['values']),array_keys($fields)))throw new RuntimeException('invalid_module_fields');
   foreach($input['values'] as $key=>$value){
    if(!is_scalar($value)&&$value!==null)throw new RuntimeException('invalid_module_fields');
    if(is_string($value)&&mb_strlen($value)>20000)throw new RuntimeException('invalid_module_fields');
    if($fields[$key]['options']&&!in_array((string)$value,array_column($fields[$key]['options'],'value'),true))throw new RuntimeException('invalid_module_fields');
   }
  }
  $dir=storage_path('app/private/site-control/operations');if(!is_dir($dir))mkdir($dir,0700,true);
  $path=$dir.'/'.$input['request_id'].'.json';$lock=fopen($dir.'/writes.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('module_busy');
  try{
   $hash=hash('sha256',json_encode($input,JSON_THROW_ON_ERROR));
   if(is_file($path)){$previous=json_decode(file_get_contents($path),true);if(($previous['hash']??'')!==$hash)throw new RuntimeException('request_conflict');if(isset($previous['result']))return $previous['result'];throw new RuntimeException('module_operation_unknown');}
   $admin=\Alphasky\ACL\Models\User::query()->where('super_user',1)->orderBy('id')->first();if(!$admin)throw new RuntimeException('site_admin_unavailable');
   Auth::guard('web')->setUser($admin);
   $result=DB::transaction(function()use($input,$action,$schema,$class,$admin,$path,$hash){
    $row=$action==='create'?null:$class::query()->lockForUpdate()->find($input['id']);
    if($action!=='create'&&(!$row||!hash_equals($this->revision($row),$input['version'])))throw new RuntimeException('record_changed');
    $method=match($action){'create'=>'store','update'=>'update','delete'=>'destroy'};
    $base=Request::create('/admin/'.$schema['module'].'/'.$schema['key'], $action==='create'?'POST':($action==='delete'?'DELETE':'PUT'),$input['values']??[]);
    $base->headers->set('Accept','application/json');$base->setUserResolver(fn()=>$admin);
    $route=new \Illuminate\Routing\Route([$base->method()],$base->path(),fn()=>null);$route->name($schema['module'].'.'.$schema['key'].'.'.$method);$route->bind($base);
    if($row)$route->setParameter($schema['key'],$row);
    $base->setRouteResolver(fn()=>$route);app()->instance('request',$base);
    $controller=app($schema['controller']);$reflection=new \ReflectionMethod($controller,$method);$arguments=[];
    foreach($reflection->getParameters() as $param){
     $type=$param->getType();$type=$type instanceof \ReflectionNamedType?$type->getName():'';
     if($type===$class){$arguments[]=$row;$route->setParameter($param->getName(),$row);}
     elseif(is_a($type,\Illuminate\Foundation\Http\FormRequest::class,true)){
      $request=$type::createFrom($base);$request->setContainer(app())->setRedirector(app('redirect'));$request->setUserResolver(fn()=>$admin);$request->setRouteResolver(fn()=>$route);$request->validateResolved();$arguments[]=$request;app()->instance('request',$request);
     }else throw new RuntimeException('module_form_unsupported');
    }
    // A persisted pending receipt prevents a blind retry after a disconnected/ambiguous write.
    file_put_contents($path,json_encode(['hash'=>$hash,'status'=>'pending']),LOCK_EX);chmod($path,0600);
    $response=$reflection->invokeArgs($controller,$arguments);
    if($response instanceof \Illuminate\Contracts\Support\Responsable)$response=$response->toResponse($base);
    if($response instanceof \Symfony\Component\HttpFoundation\Response){$payload=json_decode($response->getContent(),true);if($response->getStatusCode()>=400||!empty($payload['error']))throw new RuntimeException('module_write_failed');}
    return ['saved'=>true,'action'=>$action];
   });
   file_put_contents($path,json_encode(['hash'=>$hash,'result'=>$result]),LOCK_EX);return $result;
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}
