<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Tenant-only settings API, used by the authenticated owner broker and central JSON workflow. */
final class DesignSettings
{
    public function registry(): array
    {
        $groups = [
            'general' => ['request'=>'GeneralSettingRequest','fields'=>['admin_email','time_zone','enable_send_error_reporting_via_email']],
            'admin-appearance' => ['request'=>'AdminAppearanceRequest','fields'=>['login_screen_backgrounds','admin_logo','admin_logo_max_height','admin_favicon','admin_favicon_type','admin_title','admin_appearance_locale','admin_appearance_locale_direction','rich_editor','enable_page_visual_builder','admin_appearance_layout','admin_appearance_show_menu_item_icon','admin_appearance_show_get_started','admin_appearance_container_width','admin_primary_font','admin_primary_color','admin_secondary_color','admin_heading_color','admin_text_color','admin_link_color','admin_link_hover_color']],
            'cache' => ['request'=>'CacheSettingRequest','fields'=>['cache_admin_menu_enable','enable_cache_site_map','cache_front_menu_enabled','cache_user_avatar_enabled','shortcode_cache_enabled','widget_cache_enabled','plugin_cache_enabled','cache_time_site_map','shortcode_cache_ttl','widget_cache_ttl']],
            'datatables' => ['request'=>'DataTableSettingRequest','fields'=>['datatables_default_show_column_visibility','datatables_default_show_export_button','datatables_default_enable_responsive','datatables_pagination_type']],
            'phone-number' => ['request'=>'PhoneNumberSettingRequest','fields'=>['phone_number_enable_country_code','phone_number_available_countries','phone_number_min_length','phone_number_max_length']],
            'media' => ['request'=>'MediaSettingRequest','fields'=>['media_turn_off_automatic_url_translation_into_latin','media_use_original_name_for_file_path','media_keep_original_file_size_and_quality','media_image_quality','media_default_placeholder_image','max_upload_filesize','media_chunk_enabled','media_chunk_size','media_max_file_size','media_watermark_enabled','media_watermark_source','media_watermark_size','media_watermark_opacity','media_watermark_position','media_watermark_position_x','media_watermark_position_y','media_thumbnail_crop_position','media_convert_image_to_webp','media_enable_thumbnail_sizes','media_reduce_large_image_size','media_image_max_width','media_image_max_height','media_convert_file_name_to_uuid']],
            'email' => ['request'=>'EmailSettingRequest','fields'=>['email_from_name','email_from_address','email_default_locale']],
        ];
        // Reviewed plugins may register additional settings; never accept registry definitions from AI JSON.
        return apply_filters('website_design_settings_registry', $groups + DesignSettingsExtensions::groups());
    }

    private function rules(array $group, array $values = []): array
    {
        if(isset($group['rules']))return $group['rules'];
        $class = $group['request'];
        if (!str_contains($class, '\\')) $class = 'Alphasky\\Setting\\Http\\Requests\\'.$class;
        $request = new $class(); $request->replace($values);
        return $request->rules();
    }

    private function current(string $key, array $rules): mixed
    {
        $value = setting($key);
        $rules = is_array($rules) ? $rules : [$rules];
        if (in_array('array', $rules, true) && is_string($value)) return json_decode($value, true) ?: [];
        return $value;
    }

    public function inventory(): array
    {
        $out = [];
        foreach ($this->registry() as $id=>$group) {
            $rules = $this->rules($group); $fields = [];
            foreach ($group['fields'] as $key) {
                if (!isset($rules[$key]) || preg_match('/password|secret|token|credential|custom_.*(?:js|css)|sendmail_path/i', $key)) continue;
                $fieldRules = is_array($rules[$key]) ? $rules[$key] : [$rules[$key]];
                if(array_filter($fieldRules,fn($r)=>$r instanceof \Alphasky\Base\Rules\OnOffRule))$fieldRules[]='in:0,1';
                $constraints = array_values(array_filter(array_map(fn($r)=>is_string($r)?$r:(method_exists($r,'__toString')?(string)$r:null),$fieldRules)));
                $fields[$key] = ['value'=>$this->current($key,$fieldRules),'rules'=>$constraints];
            }
            $out[$id] = ['fields'=>$fields,'requires_confirmation'=>($group['confirmation']??in_array($id,['general','email'],true)),'dependencies'=>$group['dependencies']??[]];
        }
        return ['version'=>1,'groups'=>$out,'actions'=>['backup.create'=>['requires'=>['backup'],'scope'=>'Database and uploaded media, not server configuration or source code.']],
            'installed_plugins'=>array_values(get_active_plugins()),'protected_settings'=>DesignSettingsExtensions::protectedSettings(),
            'unavailable'=>'Credentials, executable custom code, destructive maintenance and unregistered settings require a reviewed adapter; never guess or bypass them.'];
    }

    public static function validateEnvelope(array $plan): void
    {
        if (array_diff(array_keys($plan),['version','action','group','values','expected','confirmed','name','include_media']) || ($plan['version']??null)!==1) throw new RuntimeException('invalid_settings_plan');
        if (!in_array($plan['action']??null,['settings.update','backup.create'],true)) throw new RuntimeException('unsupported_settings_action');
        if (($plan['action']??'')==='settings.update' && (!is_string($plan['group']??null)||!is_array($plan['values']??null)||!$plan['values']||count($plan['values'])>60||!is_array($plan['expected']??null))) throw new RuntimeException('invalid_settings_values');
        if (strlen(json_encode($plan,JSON_THROW_ON_ERROR))>30000) throw new RuntimeException('settings_plan_too_large');
    }

    public function apply(string $operation, array $plan): array
    {
        self::validateEnvelope($plan);
        if (!preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D',$operation)) throw new RuntimeException('invalid_settings_operation');
        $root=storage_path('app/private/design-settings');
        if (!is_dir($root)) mkdir($root,0700,true);
        if(is_link($root))throw new RuntimeException('unsafe_settings_path');
        $lock=fopen($root.'/operation.lock','c'); if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('settings_locked');
        try {
            $file=$root.'/'.$operation.'.json'; $hash=hash('sha256',json_encode($plan,JSON_THROW_ON_ERROR));
            if(is_file($file)) {
                $old=json_decode(file_get_contents($file),true,64,JSON_THROW_ON_ERROR);
                if(($old['hash']??'')!==$hash)throw new RuntimeException('settings_operation_conflict');
                if(($old['status']??'')!=='completed')throw new RuntimeException('settings_requires_reconciliation');
                return $old['result'];
            }
            if($plan['action']==='settings.update')$this->validateUpdate($plan);
            file_put_contents($file,json_encode(['hash'=>$hash,'status'=>'applying','plan'=>$plan],JSON_THROW_ON_ERROR),LOCK_EX);chmod($file,0600);
            $result=$plan['action']==='backup.create'?$this->backup($plan):$this->update($plan);
            file_put_contents($file,json_encode(['hash'=>$hash,'status'=>'completed','result'=>$result],JSON_THROW_ON_ERROR),LOCK_EX);
            return $result;
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }

    private function validateUpdate(array $plan): void
    {
        $group=$this->registry()[$plan['group']]??null;
        if(array_diff(array_keys($plan['values']),array_keys($this->inventory()['groups'][$plan['group']]['fields']??[])))throw new RuntimeException('unsupported_settings_fields');
        if(!$group || array_diff(array_keys($plan['values']),$group['fields']))throw new RuntimeException('unsupported_settings_fields');
        if(($group['confirmation']??in_array($plan['group'],['general','email'],true))&&($plan['confirmed']??false)!==true)throw new RuntimeException('settings_confirmation_required');
        foreach($group['dependencies']??[] as$plugin)if(!is_plugin_active($plugin))throw new RuntimeException('settings_dependency_missing');
        $baseRules=$this->rules($group);$current=[];foreach($group['fields']as$key)if(isset($baseRules[$key]))$current[$key]=$this->current($key,(array)$baseRules[$key]);
        $rules=$this->rules($group,array_replace($current,$plan['values']));$selected=[];$data=[];
        foreach($group['fields']as$key)if(isset($rules[$key]))$data[$key]=$this->current($key,(array)$rules[$key]);
        foreach($plan['values'] as $key=>$value) {
            if(!array_key_exists($key,$plan['expected'])||$data[$key]!==$plan['expected'][$key])throw new RuntimeException('settings_changed');
            $selected[$key]=$rules[$key];
            foreach($rules as $name=>$rule)if(str_starts_with($name,$key.'.'))$selected[$name]=$rule;
        }
        Validator::make(array_replace($data,$plan['values']),$selected)->validate();
        DesignSettingsExtensions::validate($plan['group'],$plan['values'],$data);
        array_walk_recursive($plan['values'],static function($value){if(is_string($value)&&preg_match('/[\x00<>]/u',$value))throw new RuntimeException('invalid_settings_text');});
        if(isset($plan['values']['admin_appearance_locale'])){
            $locale=$plan['values']['admin_appearance_locale'];
            if(!preg_match('/^[a-z]{2}(?:[_-][A-Za-z]{2,4})?$/D',$locale))throw new RuntimeException('invalid_admin_locale');
            if(!isset($plan['values']['admin_appearance_locale_direction']))throw new RuntimeException('locale_direction_required');
            foreach(['language','language-advanced']as$name)if(!is_plugin_active($name))throw new RuntimeException('settings_dependency_missing');
        }
    }

    private function update(array $plan): array
    {
        $values=$plan['values'];
        if(isset($values['admin_appearance_locale'])&&!is_dir(lang_path($values['admin_appearance_locale']))) {
            (new \Alphasky\Base\Services\DownloadLocaleService())->handle($values['admin_appearance_locale'],false);
            if(!is_dir(lang_path($values['admin_appearance_locale'])))throw new RuntimeException('admin_translation_unavailable');
        }
        do_action('core_before_update_settings',$values,'');
        foreach($values as$key=>$value)setting()->set($key,is_array($value)?json_encode($value,JSON_THROW_ON_ERROR):(string)$value,true);
        setting()->save();
        DesignSettingsExtensions::after($plan['group'],$values);
        \Alphasky\Base\Facades\DashboardMenu::clearCaches();
        do_action('core_after_update_settings',$values,'');
        cache()->forget('core.base.boot_settings');
        foreach($values as$key=>$value){$expected=is_array($value)?json_encode($value,JSON_THROW_ON_ERROR):(string)$value;if((string)setting($key)!==$expected)throw new RuntimeException('settings_verification_failed');}
        return ['verified'=>true,'action'=>'settings.update','group'=>$plan['group'],'fields'=>array_keys($values)];
    }

    private function backup(array $plan): array
    {
        if(!is_plugin_active('backup')||!class_exists(\Alphasky\Backup\Supports\Backup::class))throw new RuntimeException('backup_dependency_missing');
        Validator::make($plan,['name'=>'required|string|max:120','include_media'=>'required|boolean'])->validate();
        $backup=app(\Alphasky\Backup\Supports\Backup::class);
        $data=$backup->createBackupFolder($plan['name'],'Created by the authenticated Runure website owner.');$key=$data['key'];
        if(!$backup->backupDb($key))throw new RuntimeException('backup_database_failed');
        if($plan['include_media'])$backup->backupFolder(\Alphasky\Media\Facades\RvMedia::getUploadPath(),$key);
        $files=glob($backup->getBackupPath($key).'/*')?:[];$database=false;$media=!$plan['include_media'];
        foreach($files as$file){if(!is_file($file)||filesize($file)===0)continue;chmod($file,0600);if(str_starts_with(basename($file),'database-'))$database=true;if(str_starts_with(basename($file),'storage-'))$media=true;}
        if(!$database||!$media)throw new RuntimeException('backup_verification_failed');
        return ['verified'=>true,'action'=>'backup.create','backup_id'=>$key,'scope'=>$plan['include_media']?'database_and_uploads':'database'];
    }
}
