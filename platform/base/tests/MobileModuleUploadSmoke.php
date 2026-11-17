<?php
require $argv[1].'/vendor/autoload.php';require $argv[2];
class RvMedia {public static array $files=[];public static function handleUpload($file,$folder,$name){self::$files[]=[$file->getClientOriginalName(),$file->getMimeType()];return ['error'=>false,'data'=>(object)['url'=>'mobile/test.png']];}}
$upload=new Alphasky\Base\Supports\WebsiteDesign\ModuleFormUploads();
$png='iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
if($upload(['__upload'=>true,'name'=>'../../evil.php','data'=>$png],'image')!=='mobile/test.png')throw new Exception('Upload failed');
if(!preg_match('/^[a-f0-9]{32}\.png$/D',RvMedia::$files[0][0]))throw new Exception('Unsafe filename');
foreach([['__upload'=>true,'name'=>'file.php','data'=>base64_encode('<?php phpinfo();')],['__upload'=>true,'name'=>'x.png','data'=>'INVALID!']] as $bad){try{$upload($bad,'image');throw new Exception('Not rejected');}catch(RuntimeException $e){if($e->getMessage()!=='invalid_module_fields')throw $e;}}
if(count(RvMedia::$files)!==1)throw new Exception('Bad file reached media storage');echo "OK upload MIME, path, malformed base64 rejection\n";
