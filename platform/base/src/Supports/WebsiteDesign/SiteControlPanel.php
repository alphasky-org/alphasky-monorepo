<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use Alphasky\Page\Models\Page;
use Alphasky\Slug\Models\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Invoked only by the hosting broker as the tenant OS user; no public HTTP route. */
final class SiteControlPanel
{
    public function execute(array $input): array
    {
        (new DesignUiBlocks())->inventory(); // Initialize the same theme registry used by the real page editor.
        return match ($input['action'] ?? '') {
            'modules', 'module_rows', 'module_create', 'module_update', 'module_delete' => (new SiteModules())->execute($input),
            'dashboard' => $this->dashboard(),
            'pages' => ['pages' => Page::query()->orderBy('id')->get(['id','name','status','updated_at'])->toArray()],
            'page' => $this->page((int) ($input['id'] ?? 0)),
            'save_page' => $this->savePage($input),
            'settings' => $this->settings(),
            'save_settings' => $this->saveSettings($input),
            'members' => $this->members($input),
            default => throw new RuntimeException('unsupported_action'),
        };
    }

    private function dashboard(): array
    {
        $members = function_exists('is_plugin_active') && is_plugin_active('member');
        return ['pages' => Page::query()->count(), 'published_pages' => Page::query()->where('status','published')->count(),
            'members_enabled' => $members, 'members' => $members ? \Alphasky\Member\Models\Member::query()->count() : null,
            'ui_blocks' => count(shortcode()->getAll()), 'site_name' => theme_option('site_title', config('app.name')),
            'url' => config('app.url')];
    }

    public static function chunks(string $content): array
    {
        // Retain all surrounding HTML verbatim. Only complete, non-nested blocks are editable.
        preg_match_all('~\[([a-z][a-z0-9_-]*)\b((?:"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|[^\]"\'])*)\](.*?)\[/\1\]~s', $content, $matches, PREG_OFFSET_CAPTURE);
        $chunks=[]; $offset=0;
        foreach($matches[0] as $i=>$match){
            if($match[1]>$offset)$chunks[]=['raw'=>substr($content,$offset,$match[1]-$offset)];
            $chunks[]=['raw'=>$match[0],'name'=>$matches[1][$i][0],'body'=>$matches[3][$i][0]];
            $offset=$match[1]+strlen($match[0]);
        }
        if($offset<strlen($content))$chunks[]=['raw'=>substr($content,$offset)];
        return $chunks;
    }

    private function snapshot(Page $page): array
    {
        return ['name'=>$page->name,'content'=>(string)$page->getRawOriginal('content'),'slug'=>(string)Slug::query()->where('reference_type',Page::class)->where('reference_id',$page->id)->value('key')];
    }
    private function hash(array $snapshot): string { return hash('sha256',json_encode($snapshot,JSON_THROW_ON_ERROR)); }
    private function page(int $id): array
    {
        $page=Page::query()->find($id); if(!$page)throw new RuntimeException('page_unavailable');
        $snapshot=$this->snapshot($page);$blocks=[];
        foreach(self::chunks($snapshot['content']) as $index=>$chunk){
            if(!isset($chunk['name']))continue;
            $info=shortcode()->getAll()[$chunk['name']]??[];
            $fields=($info['design_schema']['kind']??'')==='content'?$info['design_schema']['fields']:[];
            $attributes=shortcode()->getCompiler()->getAttributes($chunk['raw']);$values=[];
            foreach($fields as $field){$value=$attributes[$field['name']]??($field['default']??'');if($field['type']==='repeater'&&is_string($value))$value=self::decodeRows($value);$values[$field['name']]=$value;}
            if(in_array($chunk['name'],['google-map','youtube-video'],true)){$fields=[['name'=>'content','type'=>'text','label'=>$chunk['name']==='google-map'?'Address':'YouTube URL']];$values=['content'=>$chunk['body']];}
            $blocks[]=['index'=>$index,'name'=>$chunk['name'],'title'=>$info['name']??$chunk['name'],'fields'=>$fields,'values'=>$values];
        }
        return ['id'=>$id,'name'=>$snapshot['name'],'slug'=>$snapshot['slug'],'version'=>$this->hash($snapshot),'blocks'=>$blocks,'content'=>$snapshot['content']];
    }

    public static function decodeRows(string $value): array
    {
        if(str_starts_with($value,'json64:')){$encoded=substr($value,7);$encoded.=str_repeat('=',(4-strlen($encoded)%4)%4);$value=base64_decode(strtr($encoded,'-_','+/'),true)?:'[]';}
        $rows=json_decode(html_entity_decode($value,ENT_QUOTES),true);
        if(!is_array($rows))return [];
        return array_values(array_map(function($row){if(!is_array($row))return [];foreach($row as $item)if(is_array($item)&&array_key_exists('key',$item))return array_column($row,'value','key');return $row;},$rows));
    }

    public static function validateFields(array $fields,array $values): array
    {
        if(array_diff(array_keys($values),array_column($fields,'name')))throw new RuntimeException('invalid_block_fields');
        $out=[];
        foreach($values as $key=>$value){
            $field=$fields[array_search($key,array_column($fields,'name'),true)];
            if(($field['type']??'')==='repeater'){
                if(!is_array($value)||!array_is_list($value)||count($value)>100)throw new RuntimeException('invalid_block_fields');
                $out[$key]=array_map(fn($row)=>is_array($row)?self::validateFields($field['fields']??[],$row):throw new RuntimeException('invalid_block_fields'),$value);
            }else{
                if(!is_string($value)||mb_strlen($value)>10000||preg_match('/[<>\[\]\x00]/u',$value))throw new RuntimeException('invalid_block_fields');
                if(($field['type']??'')==='color'&&$value!==''&&!preg_match('/^#[a-fA-F0-9]{6}$/D',$value))throw new RuntimeException('invalid_color');
                $out[$key]=$value;
            }
        }
        return $out;
    }

    private function savePage(array $input): array
    {
        Validator::make($input,['id'=>'required|integer|min:1','name'=>'required|string|max:250','slug'=>'required|string|max:200|regex:/^[\pL\pN_-]+$/u','version'=>'required|string|size:64','order'=>'present|array|max:200','order.*'=>'integer|min:0','edits'=>'present|array|max:200'])->validate();
        return DB::transaction(function()use($input){
            $page=Page::query()->lockForUpdate()->find($input['id']);if(!$page)throw new RuntimeException('page_unavailable');
            $snapshot=$this->snapshot($page);if(!hash_equals($this->hash($snapshot),$input['version']))throw new RuntimeException('page_changed');
            $chunks=self::chunks($snapshot['content']);$available=[];foreach($chunks as $i=>$chunk)if(isset($chunk['name']))$available[]=$i;
            if(array_diff(array_keys($input['edits']),$available))throw new RuntimeException('invalid_block_fields');
            $order=$input['order'];$sorted=$order;sort($sorted);if($sorted!==$available)throw new RuntimeException('invalid_block_order');
            $rendered=[];
            foreach($available as $i){
                $chunk=$chunks[$i];$values=$input['edits'][(string)$i]??null;
                if($values===null){$rendered[$i]=$chunk['raw'];continue;}
                $schema=shortcode()->getAll()[$chunk['name']]['design_schema']??null;
                if(in_array($chunk['name'],['google-map','youtube-video'],true)){
                    if(array_keys($values)!==['content'])throw new RuntimeException('invalid_block_fields');
                    $rendered[$i]=(new DesignUiBlocks())->block(['name'=>$chunk['name'],'attributes'=>shortcode()->getCompiler()->getAttributes($chunk['raw']),'content'=>$values['content']]);continue;
                }
                if(($schema['kind']??'')!=='content')throw new RuntimeException('block_not_editable');
                $values=self::validateFields($schema['fields'],$values);$attrs=shortcode()->getCompiler()->getAttributes($chunk['raw']);
                foreach($values as $key=>$value)$attrs[$key]=is_array($value)?'json64:'.rtrim(strtr(base64_encode(json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),'+/','-_'),'='):$value;
                $rendered[$i]=shortcode()->generateShortcode($chunk['name'],$attrs,$chunk['body']);
            }
            $content='';$position=0;foreach($chunks as $chunk)$content.=isset($chunk['name'])?$rendered[$order[$position++]]:$chunk['raw'];
            $slug=Slug::query()->where('reference_type',Page::class)->where('reference_id',$page->id)->lockForUpdate()->first();
            if(!$slug)throw new RuntimeException('slug_unavailable');
            if(Slug::query()->where('key',$input['slug'])->where('prefix',$slug->prefix)->where('id','!=',$slug->id)->exists())throw new RuntimeException('slug_taken');
            $dir=storage_path('app/private/site-control');if(!is_dir($dir))mkdir($dir,0700,true);
            $backup=$dir.'/'.$page->id.'-'.$this->hash($snapshot).'.json';if(!is_file($backup)){file_put_contents($backup,json_encode($snapshot,JSON_THROW_ON_ERROR),LOCK_EX);chmod($backup,0600);}
            // Raw write preserves visual-builder HTML rather than round-tripping it through a sanitizer.
            DB::table('pages')->where('id',$page->id)->update(['name'=>strip_tags($input['name']),'content'=>$content,'updated_at'=>now()]);
            $slug->key=$input['slug'];$slug->save();
            \Illuminate\Support\Facades\Cache::flush();
            return $this->page((int)$page->id);
        });
    }

    private function settings(): array
    {
        $data=['admin_email'=>(string)setting('admin_email',''),'site_title'=>(string)theme_option('site_title',''),
            'primary'=>(string)setting('website_design_primary',theme_option('primary_color','#2354c9')),
            'accent'=>(string)setting('website_design_accent',theme_option('primary_color_hover','#8051d8')),
            'social_links'=>array_map(fn($link)=>['label'=>(string)$link->getName(),'url'=>(string)$link->getUrl()],\Alphasky\Theme\Supports\ThemeSupport::getSocialLinks())];
        return $data+['version'=>$this->hash($data)];
    }
    private function saveSettings(array $input): array
    {
        Validator::make($input,['version'=>'required|string|size:64','admin_email'=>'required|email|max:250','site_title'=>'required|string|max:200','primary'=>'required|regex:/^#[a-fA-F0-9]{6}$/D','accent'=>'required|regex:/^#[a-fA-F0-9]{6}$/D','social_links'=>'present|array|max:10','social_links.*.label'=>'required|string|max:40','social_links.*.url'=>'required|url:https|max:1000'])->validate();
        $lock=\Illuminate\Support\Facades\Cache::lock('site-control-settings',30);
        return $lock->block(3,function()use($input){
            if(!hash_equals($this->settings()['version'],$input['version']))throw new RuntimeException('settings_changed');
            (new DesignPublisher())->brand(['primary'=>$input['primary'],'accent'=>$input['accent']]);
            $social=array_map(fn($s)=>['label'=>strip_tags($s['label']),'url'=>$s['url']],$input['social_links']);
            setting()->set(['admin_email'=>$input['admin_email']])->save();
            $old=json_decode((string)theme_option('social_links','[]'),true)?:[];$links=[];
            foreach($social as $link){$extras=[];foreach($old as $row){$values=array_column($row,'value','key');if(($values['name']??'')===$link['label']){$extras=array_diff_key($values,array_flip(['name','url']));break;}}$values=['name'=>$link['label'],'url'=>$link['url']]+$extras;$links[]=array_map(fn($key)=>['key'=>$key,'value'=>$values[$key]],array_keys($values));}
            \Alphasky\Theme\Facades\ThemeOption::setOption('social_links',json_encode($links,JSON_THROW_ON_ERROR))->saveOptions();
            \Alphasky\Theme\Facades\ThemeOption::setOption('site_title',strip_tags($input['site_title']))->saveOptions();
            return $this->settings();
        });
    }
    private function members(array $input): array
    {
        if(!function_exists('is_plugin_active')||!is_plugin_active('member'))throw new RuntimeException('members_unavailable');
        $page=max(1,min(100000,(int)($input['page']??1)));
        $query=\Alphasky\Member\Models\Member::query();
        return ['total'=>$query->count(),'page'=>$page,'members'=>$query->orderByDesc('id')->skip(($page-1)*30)->take(30)->get(['id','first_name','last_name','email','created_at'])->toArray()];
    }
}
