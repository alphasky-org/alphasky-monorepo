<?php
namespace Alphasky\Base\Supports\WebsiteDesign;
use RuntimeException;
use Illuminate\Http\UploadedFile;
final class ModuleFormUploads {
 private int $bytes=0;
 private array $created=[];
 public function rollback():void {foreach($this->created as $file){try{\RvMedia::deleteFile($file);}catch(\Throwable){}}$this->created=[];}
 public function __invoke(array $value,string $type):string {
  if(($value['__upload']??null)!==true||!is_string($value['data']??null)||strlen($value['data'])>14000000)throw new RuntimeException('invalid_module_fields');
  $data=base64_decode($value['data'],true);if($data===false||strlen($data)>10485760||strlen($data)===0||($this->bytes+=strlen($data))>16777216)throw new RuntimeException('invalid_module_fields');
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($data);
  $extensions=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','application/pdf'=>'pdf','text/plain'=>'txt','text/csv'=>'csv','audio/mpeg'=>'mp3','audio/mp4'=>'m4a','audio/x-wav'=>'wav','audio/wav'=>'wav','audio/ogg'=>'ogg','video/webm'=>'webm','audio/webm'=>'webm','application/msword'=>'doc','application/vnd.ms-excel'=>'xls','application/vnd.ms-powerpoint'=>'ppt','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx','application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'pptx'];
  $originalExtension=strtolower(pathinfo((string)($value['name']??''),PATHINFO_EXTENSION));
  if($mime==='application/zip'&&in_array($originalExtension,['docx','xlsx','pptx'],true))$extensions[$mime]=$originalExtension;
  if(in_array($mime,['application/x-ole-storage','application/CDFV2'],true)&&in_array($originalExtension,['doc','xls','ppt'],true))$extensions[$mime]=$originalExtension;
  if(!isset($extensions[$mime]))throw new RuntimeException('invalid_module_fields');
  if(in_array($type,['image','images','signature','sign'],true)&&!str_starts_with($mime,'image/'))throw new RuntimeException('invalid_module_fields');
  if($type==='audio'&&!str_starts_with($mime,'audio/')&&$mime!=='video/webm')throw new RuntimeException('invalid_module_fields');
  $path=tempnam(sys_get_temp_dir(),'module-upload-');if(!$path)throw new RuntimeException('module_upload_failed');chmod($path,0600);
  try{file_put_contents($path,$data);$file=new UploadedFile($path,bin2hex(random_bytes(16)).'.'.$extensions[$mime],$mime,null,true);$response=\RvMedia::handleUpload($file,0,'mobile-modules');
   if(!empty($response['error'])||empty($response['data']))throw new RuntimeException('module_upload_failed');$media=$response['data'];$url=$media->url??$media->resource->url??null;if(!is_string($url)||$url==='')throw new RuntimeException('module_upload_failed');$record=$media instanceof \Alphasky\Media\Models\MediaFile?$media:($media->resource??null);if($record instanceof \Alphasky\Media\Models\MediaFile)$this->created[]=$record;return $url;
  }finally{if(is_file($path))unlink($path);}
 }
}
