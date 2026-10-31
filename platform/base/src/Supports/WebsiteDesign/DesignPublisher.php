<?php
namespace Alphasky\Base\Supports\WebsiteDesign;
use Alphasky\Page\Models\Page;
use Illuminate\Support\Facades\DB;
use RuntimeException;
final class DesignPublisher {
 public function homepage(string $module,string $shortcode,array $sections=[]):int {
  if(!preg_match('/^design_[a-f0-9]{12}_frontend$/D',$module)||$shortcode!==str_replace('_','-',$module).'-home')throw new RuntimeException('invalid_homepage');
  return DB::transaction(function()use($module,$shortcode,$sections){
   $key='website_design_homepage_'.$module;$id=(int)setting($key,0);
   $page=$id?Page::query()->lockForUpdate()->find($id):null;
   if(!$page){$page=new Page();$page->name='Home';$page->user_id=DB::table('users')->where('super_user',1)->value('id');}
   $current=(string)$page->getRawOriginal('content');
   $next=self::homepageContent($current,$shortcode,$sections);
   if($current!==''&&$next!==$current){$dir=storage_path('app/private/website-design/page-backups');if(!is_dir($dir))mkdir($dir,0700,true);$file=$dir.'/split-'.$page->id.'-'.hash('sha256',$current).'.json';if(!is_file($file)){file_put_contents($file,json_encode(['page_id'=>$page->id,'content'=>$current],JSON_THROW_ON_ERROR),LOCK_EX);chmod($file,0600);}}
   $page->content=self::homepageContent($current,$shortcode,$sections);$page->template=$sections?'website-design':'no-sidebar';$page->status='published';$page->save();
   if(!setting('website_design_previous_homepage'))setting()->set('website_design_previous_homepage',(string)theme_option('homepage_id',''));
   setting()->set($key,(string)$page->id)->save();
   \Alphasky\Theme\Facades\ThemeOption::setOption('homepage_id',(string)$page->id)->saveOptions();return (int)$page->id;
  });
 }
 public static function homepageContent(string $current,string $shortcode,array $sections=[]):string {
  if($sections){
   $prefix=substr($shortcode,0,-4);
   foreach($sections as $section)if(!in_array($section,array_map(fn($s)=>$prefix.$s,['navigation','slider','about','services','contact','footer']),true))throw new RuntimeException('invalid_homepage_section');
   $pattern='/\\['.preg_quote($shortcode,'/').'(\\s[^\\]]*)?\\](.*?)\\[\\/'.preg_quote($shortcode,'/').'\\]/s';
   if(preg_match($pattern,$current))return preg_replace_callback($pattern,fn($m)=>implode("\n",array_map(fn($name)=>'['.$name.($m[1]??'').'][/'.$name.']',$sections)),$current,1);
   // Any existing section means an editor controls ordering/removal; never restore deleted blocks.
   foreach($sections as $section)if(str_contains($current,'['.$section))return $current;
   return $current.($current!==''?"\n":'').implode("\n",array_map(fn($name)=>'['.$name.'][/'.$name.']',$sections));
  }
  // Updating the generated module must not discard other UI Blocks or editor content.
  if(preg_match('/\\['.preg_quote($shortcode,'/').'(?:\\s|\\])/',$current))return $current;
  return $current.($current!==''?"\n":'').'['.$shortcode.'][/'.$shortcode.']';
 }
 public function brand(array $data):array {
  $changes=[];foreach(['primary','accent']as$key)if(isset($data[$key])){if(!is_string($data[$key])||!preg_match('/^#[a-f0-9]{6}$/iD',$data[$key]))throw new RuntimeException('invalid_color');$changes['website_design_'.$key]=$data[$key];}
  if(isset($data['logo_base64'])){
   $bytes=base64_decode($data['logo_base64'],true);if($bytes===false||strlen($bytes)>3000000)throw new RuntimeException('invalid_logo');
   $size=@getimagesizefromstring($bytes);if(!$size||!in_array($size[2],[IMAGETYPE_PNG,IMAGETYPE_JPEG,IMAGETYPE_WEBP],true)||$size[0]>4096||$size[1]>4096)throw new RuntimeException('invalid_logo');
   $im=@imagecreatefromstring($bytes);if(!$im)throw new RuntimeException('invalid_logo');imagesavealpha($im,true);
   $dir=public_path('website-design');if(is_link($dir))throw new RuntimeException('unsafe_logo_directory');if(!is_dir($dir))mkdir($dir,0755,true);
   $name='logo-'.hash('sha256',$bytes).'.png';$path=$dir.'/'.$name;if(is_link($path))throw new RuntimeException('unsafe_logo_path');if(!imagepng($im,$path))throw new RuntimeException('logo_save_failed');imagedestroy($im);chmod($path,0644);
   $changes['website_design_logo']='/website-design/'.$name;
  }
  if(!$changes)throw new RuntimeException('brand_empty');
  setting()->set($changes)->save();
  $theme=\Alphasky\Theme\Facades\ThemeOption::getFacadeRoot();if(isset($data['primary']))$theme->setOption('primary_color',$data['primary']);if(isset($data['accent']))$theme->setOption('primary_color_hover',$data['accent']);if(isset($changes['website_design_logo']))$theme->setOption('logo',$changes['website_design_logo']);$theme->saveOptions();
  return ['message'=>'تم تحديث الشعار وألوان الموقع.','brand'=>array_intersect_key($changes,array_flip(['website_design_primary','website_design_accent','website_design_logo']))];
 }
}
