<?php
namespace Alphasky\Base\Supports;

use Illuminate\Support\Facades\Http;

/** Opt-in SMTP discovery for managed sites. Credentials are never persisted in JSON or logs. */
final class ManagedMail
{
    public static function configure(): void
    {
        $endpoint = (string) config('core.base.general.managed_mail_url');
        if ($endpoint === '') return;
        $domain = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if (parse_url($endpoint, PHP_URL_SCHEME) !== 'https' || !$domain) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sender' => self::unavailable()]);
        }
        try {
            $response = Http::connectTimeout(5)->timeout(15)->withoutRedirecting()
                ->withToken((string) config('core.base.general.website_design.activation_token'))
                ->post($endpoint, ['domain' => $domain, 'sender' => setting('email_from_address')]);
        } catch (\Throwable) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sender' => self::unavailable()]);
        }
        if ($response->status() === 409 && $response->json('error') === 'mailbox_required') {
            throw \Illuminate\Validation\ValidationException::withMessages(['sender' => 'لا يوجد بريد فعّال لهذا الموقع. أنشئ بريدًا على نطاق الموقع من قسم الإيميلات في رنور، ثم أعد الإرسال. / Create an active mailbox on this website’s domain in Runure before sending email.']);
        }
        $data = $response->json();
        if (!$response->successful() || !is_array($data)
            || !filter_var($data['from'] ?? '', FILTER_VALIDATE_EMAIL)
            || strtolower(explode('@', $data['from'])[1]) !== $domain
            || ($data['username'] ?? '') !== $data['from']
            || empty($data['password']) || empty($data['host']) || ($data['port'] ?? 0) !== 587) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sender' => self::unavailable()]);
        }
        config(['mail.default' => 'smtp', 'mail.from.address' => $data['from'],
            'mail.mailers.smtp' => ['transport' => 'smtp', 'scheme' => 'smtp', 'host' => $data['host'],
                'port' => 587, 'username' => $data['username'], 'password' => $data['password'],
                'require_tls' => true, 'verify_peer' => true, 'timeout' => 20]]);
        // Only non-secret sender metadata is saved for the administrator's Email settings.
        if (setting('email_from_address') !== $data['from']) {
            setting()->set(['email_from_address' => $data['from'], 'email_driver' => 'smtp',
                'email_host' => $data['host'], 'email_port' => 587,
                'email_encryption' => 'tls', 'email_username' => $data['username']])->save();
        }
    }

    private static function unavailable(): string
    {
        return 'تعذر التحقق من إعدادات بريد الموقع. لم يتم إرسال الرسالة؛ حاول لاحقًا. / Website mail configuration could not be verified. No email was sent; please retry later.';
    }
}
