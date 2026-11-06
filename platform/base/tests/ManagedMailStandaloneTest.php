<?php
// Run: php platform/base/tests/ManagedMailStandaloneTest.php /path/to/host-app
$host=$argv[1]??exit('Host application path required');
require $host.'/vendor/autoload.php';$app=require $host.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require dirname(__DIR__).'/src/Supports/ManagedMail.php';
use Alphasky\Base\Supports\ManagedMail;use Illuminate\Support\Facades\Http;
config(['core.base.general.managed_mail_url'=>'https://runure.example/mail','core.base.general.website_design.activation_token'=>'fake-site-token','app.url'=>'https://example.com']);
setting()->set('email_from_address','admin@example.com');setting()->set('email_from_name','Custom Company');
$n=0;function check($v){global $n;if(!$v)throw new RuntimeException('Assertion '.($n+1));$n++;}
function fakeResponse($data,$code=200){Http::swap(new Illuminate\Http\Client\Factory());Http::preventStrayRequests();Http::fake(['https://runure.example/mail'=>Http::response($data,$code)]);}
function rejects(){try{ManagedMail::configure();throw new LogicException('Expected rejection');}catch(Illuminate\Validation\ValidationException $e){check(isset($e->errors()['sender']));}}
fakeResponse(['error'=>'mailbox_required'],409);rejects();fakeResponse([],403);rejects();
$data=['host'=>'mail.example.com','port'=>587,'username'=>'admin@example.com','from'=>'admin@example.com','password'=>'not-a-real-secret'];
fakeResponse($data);ManagedMail::configure();check(config('mail.default')==='smtp');check(config('mail.mailers.smtp.require_tls')===true);check(config('mail.mailers.smtp.verify_peer')===true);check(config('mail.from.address')==='admin@example.com');
Http::assertSent(fn($r)=>$r->hasHeader('Authorization','Bearer fake-site-token')&&$r['domain']==='example.com');$n++;
fakeResponse(array_replace($data,['from'=>'admin@other.com']));rejects();fakeResponse(array_replace($data,['password'=>'']));rejects();fakeResponse(array_replace($data,['port'=>25]));rejects();
config(['core.base.general.managed_mail_url'=>'http://runure.example/mail']);rejects();
config(['core.base.general.managed_mail_url'=>'','mail.default'=>'custom']);ManagedMail::configure();check(config('mail.default')==='custom');
check(ManagedMail::senderName('Custom Company','Site','App','example.com')==='Custom Company');
check(ManagedMail::senderName('Example','Future Energy','App','example.com')==='Future Energy');
check(ManagedMail::senderName(null,null,'Laravel','example.com')==='example.com');
check(ManagedMail::senderName('شركة الطاقة',null,null,'example.com')==='شركة الطاقة');
check(config('mail.from.name')==='Custom Company');
echo "Passed $n managed SMTP validation, TLS, authentication and opt-out assertions.\n";
