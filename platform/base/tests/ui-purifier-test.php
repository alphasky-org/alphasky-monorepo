<?php
require $argv[1].'/vendor/autoload.php';$app=require $argv[1].'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config=require $argv[2].'/platform/base/config/general.php';config(['purifier.settings'=>array_replace(config('purifier.settings'),$config['purifier'])]);app()->forgetInstance('purifier');
$clean=(string)Alphasky\Base\Facades\BaseHelper::clean('<div data-design-ui="11111111-2222-4333-8444-555555555555-0" onclick="evil()">[google-map]Amman[/google-map]</div><script>evil()</script>');
if(!str_contains($clean,'data-design-ui="11111111-2222-4333-8444-555555555555-0"')||str_contains($clean,'onclick')||str_contains($clean,'<script'))throw new RuntimeException('purifier_marker_failed');
echo "Purifier preserves publication marker and rejects active HTML.\n";
