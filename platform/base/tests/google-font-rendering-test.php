<?php
require $argv[1].'/vendor/autoload.php';$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Alphasky\Base\Helpers\BaseHelper;
use Alphasky\Base\Supports\Fonts;
use Illuminate\Support\Facades\Http;
Http::preventStrayRequests();
config(['core.base.general.google_fonts_enabled'=>true,'core.base.general.google_fonts_enabled_cache'=>true,'core.base.general.google_fonts_url'=>'https://fonts.bunny.net']);
$css="@font-face{font-family:Roboto;font-weight:300;unicode-range:U+0000-00FF;}\n@font-face{font-family:Roboto;font-weight:400;unicode-range:U+0400-045F;}";
$url='https://fonts.googleapis.com/css2?family=Roboto:wght@300;400&display=swap';
$local='https://tenant.example/storage/fonts/test/fonts.css';
$service=new class {public $result;public function load($url){return $this->result;}};
$service->result=new Fonts(googleFontsUrl:$url,localizedUrl:$local,localizedCss:$css,preferInline:true);$app->instance('core.google-fonts',$service);
$helper=new BaseHelper();$n=0;function check($condition){global $n;if(!$condition)throw new RuntimeException('Font rendering regression');$n++;}
$html=(string)$helper->googleFonts($url);check(str_contains($html,$local)&&str_contains($html,'rel="stylesheet"'));check(!str_contains($html,'@font-face'));
$inline=(string)$helper->googleFonts($url,true);check(str_contains($inline,$css));
$service->result=null;check(str_contains((string)$helper->googleFonts($url),'https://fonts.bunny.net/css2'));
config(['core.base.general.google_fonts_enabled_cache'=>false]);check(str_contains((string)$helper->googleFonts($url),'rel="stylesheet"'));
config(['core.base.general.google_fonts_enabled'=>false]);check($helper->googleFonts($url)==='');
echo "Passed $n default stylesheet, explicit inline, subset preservation and fallback checks.\n";
