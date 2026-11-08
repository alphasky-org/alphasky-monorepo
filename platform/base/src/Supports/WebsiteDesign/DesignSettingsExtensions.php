<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Reviewed adapters for settings outside the base settings package. */
final class DesignSettingsExtensions
{
 public static function groups():array
 {
  $groups=[
   'email-rules'=>['request'=>'EmailRulesSettingRequest','confirmation'=>true,'fields'=>['email_rules_blacklist_email_domains','email_rules_blacklist_specified_emails','email_rules_exception_emails','email_rules_strict','email_rules_dns','email_rules_spoof']],
   'email-branding'=>['request'=>'EmailTemplateSettingRequest','fields'=>['email_template_logo','email_template_email_contact','email_template_social_links','email_template_copyright_text','email_template_max_height_for_logo']],
   'optimization'=>['request'=>'Alphasky\\Optimize\\Http\\Requests\\OptimizeSettingRequest','fields'=>['optimize_page_speed_enable','optimize_collapse_white_space','optimize_elide_attributes','optimize_inline_css','optimize_insert_dns_prefetch','optimize_remove_comments','optimize_remove_quotes','optimize_defer_javascript']],
   'sitemap'=>['request'=>'Alphasky\\Sitemap\\Http\\Requests\\SitemapSettingRequest','fields'=>['sitemap_enabled','sitemap_items_per_page']],
   'tracking'=>['request'=>'Alphasky\\Theme\\Http\\Requests\\WebsiteTrackingSettingRequest','confirmation'=>true,'fields'=>['google_tag_manager_type','gtm_container_id','google_tag_manager_id','gtm_debug_mode']],
   'api'=>['request'=>'Alphasky\\Api\\Http\\Requests\\ApiSettingRequest','confirmation'=>true,'fields'=>['api_enabled','push_notifications_enabled']],
  ];
  foreach($groups as$id=>$g){$class=str_contains($g['request'],'\\')?$g['request']:'Alphasky\\Setting\\Http\\Requests\\'.$g['request'];if(!class_exists($class))unset($groups[$id]);}
  foreach(self::pluginRules() as$id=>$rules)$groups[$id]=['rules'=>$rules,'fields'=>array_values(array_filter(array_keys($rules),fn($key)=>!str_contains($key,'.'))),'confirmation'=>$id==='member','dependencies'=>match($id){'language'=>['language','language-advanced'],default=>[$id]}];
  if(class_exists(\Alphasky\Slug\Facades\SlugHelper::class)){
   $rules=[];foreach(\Alphasky\Slug\Facades\SlugHelper::supportedModels() as$model=>$name){$key=\Alphasky\Slug\Facades\SlugHelper::getPermalinkSettingKey($model);$empty=in_array($model,\Alphasky\Slug\Facades\SlugHelper::getCanEmptyPrefixes(),true);$rules[$key]=[$empty?'nullable':'required','string','max:200','regex:/^[\pL\pN_\-\/]*$/u'];}
   $rules[\Alphasky\Slug\Facades\SlugHelper::getSettingKey('public_single_ending_url')]=['nullable','string','max:20','regex:/^[a-z0-9]*$/D'];
   $rules[\Alphasky\Slug\Facades\SlugHelper::getSettingKey('slug_turn_off_automatic_url_translation_into_latin')]=['in:0,1'];
   $groups['permalinks']=['rules'=>$rules,'fields'=>array_keys($rules),'confirmation'=>true];
  }
  $templates=[];
  foreach(\Alphasky\Base\Facades\EmailHandler::getTemplates()as$type=>$modules)foreach($modules as$module=>$data)foreach($data['templates']??[]as$name=>$template){
   $templates[get_setting_email_subject_key($type,$module,$name)]=['nullable','string','max:500'];
   $templates[get_setting_email_status_key($type,$module,$name)]=['in:0,1'];
  }
  if($templates)$groups['email-templates']=['rules'=>$templates,'fields'=>array_keys($templates),'confirmation'=>true];
  return $groups;
 }
 private static function pluginRules():array
 {
  return [
   'blog'=>['blog_post_schema_enabled'=>['in:0,1'],'blog_post_schema_type'=>['nullable','in:NewsArticle,News,Article,BlogPosting']],
   'member'=>['member_enabled_login'=>['in:0,1'],'member_enabled_registration'=>['in:0,1'],'verify_account_email'=>['in:0,1'],'member_verification_expire_minutes'=>['nullable','integer','min:1','max:10080'],'member_enable_post_approval'=>['in:0,1'],'member_show_terms_checkbox'=>['in:0,1'],'member_default_avatar'=>['nullable','string','max:1000']],
   'language'=>['language_hide_default'=>['in:0,1'],'language_display'=>['required','in:all,flag,name'],'language_switcher_display'=>['required','in:dropdown,list'],'language_hide_languages'=>['nullable','array'],'language_hide_languages.*'=>['integer','exists:languages,lang_id'],'language_auto_detect_user_language'=>['in:0,1']],
  ];
 }
 public static function validate(string $group,array $values,array $current):void
 {
  if($group==='email-templates')foreach($values as$value)if(is_string($value)&&preg_match('/[{}]/u',$value))throw new RuntimeException('template_subject_plain_text_required');
  if($group==='tracking'){
   $data=array_replace($current,$values);if(!in_array($data['google_tag_manager_type']??'', ['gtm','id'],true))throw new RuntimeException('tracking_identifier_mode_required');
   $key=$data['google_tag_manager_type']==='gtm'?'gtm_container_id':'google_tag_manager_id';$pattern=$key==='gtm_container_id'?'/^GTM-[A-Z0-9]+$/D':'/^G-[A-Z0-9]+$/D';
   if(!preg_match($pattern,(string)($data[$key]??'')))throw new RuntimeException('tracking_identifier_required');
  }
  if($group==='email-rules')foreach(['email_rules_blacklist_email_domains','email_rules_blacklist_specified_emails','email_rules_exception_emails']as$key){
   if(!array_key_exists($key,$values))continue;$raw=$values[$key];$rows=$raw===null||$raw===''?[]:json_decode($raw,true);if(!is_array($rows)||!array_is_list($rows)||count($rows)>1000)throw new RuntimeException('invalid_email_rules');
   foreach($rows as$row){if(!is_array($row)||array_keys($row)!==['value']||!is_string($row['value']))throw new RuntimeException('invalid_email_rules');Validator::make(['item'=>$row['value']],['item'=>['required',$key==='email_rules_blacklist_email_domains'?new \Alphasky\Base\Rules\DomainRule():'email']])->validate();}
  }
  if($group==='email-branding'&&isset($values['email_template_social_links']))foreach($values['email_template_social_links']as$row)foreach($row as$item){if(!is_array($item))throw new RuntimeException('invalid_social_link');if(in_array($item['key']??'', ['url','image'],true)&&($item['value']??'')!=='')Validator::make(['url'=>$item['value']],['url'=>'url:https|max:2000'])->validate();}
 }
 public static function after(string $group,array $values):void
 {
  if($group==='sitemap'){\Alphasky\Base\Services\ClearCacheService::make()->clearFrameworkCache();event(new \Alphasky\Sitemap\Events\SitemapUpdatedEvent());}
  if($group==='language')\Alphasky\Language\Facades\Language::clearCache();
  if($group==='permalinks'){
   foreach(\Alphasky\Slug\Facades\SlugHelper::supportedModels()as$model=>$label){$key=\Alphasky\Slug\Facades\SlugHelper::getPermalinkSettingKey($model);if(!array_key_exists($key,$values))continue;$prefix=(string)$values[$key];\Alphasky\Slug\Models\Slug::query()->where('reference_type',$model)->update(['prefix'=>$prefix]);event(new \Alphasky\Slug\Events\UpdatedPermalinkSettings($model,$prefix,\Illuminate\Http\Request::create('/','PUT',$values)));}
   \Alphasky\Menu\Facades\Menu::clearCacheMenuItems();
  }
 }
 public static function protectedSettings():array
 {
  return [
   ['id'=>'email-credentials','path'=>'settings/email','reason'=>'Use the private admin form for SMTP/provider secrets; do not ask for them in AI chat.'],
   ['id'=>'storage-credentials','path'=>'settings/media','reason'=>'Use the private admin form for storage credentials and driver migration.'],
   ['id'=>'custom-code','path'=>'settings/admin-appearance','reason'=>'Custom JS/CSS and executable templates require code review.'],
   ['id'=>'email-template-content','path'=>'settings/email/templates','reason'=>'Individual template source and variables require template review; branding is editable via email-branding.'],
   ['id'=>'security','path'=>'settings/security','reason'=>'Environment-managed security diagnostics; do not rewrite environment files.'],
   ['id'=>'cronjob','path'=>'settings/cronjob','reason'=>'Host-managed scheduler; not a writable CMS setting.'],
   ['id'=>'api-credentials','path'=>'settings/api','reason'=>'API keys and Firebase credentials use the private settings form. API switches are supported separately.'],
   ['id'=>'indexnow','path'=>'settings/sitemap','reason'=>'IndexNow credential generation and external submission require their admin action.'],
  ];
 }
}
