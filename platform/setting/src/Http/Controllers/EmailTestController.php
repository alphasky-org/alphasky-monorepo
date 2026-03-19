<?php

namespace Alphasky\Setting\Http\Controllers;

use Alphasky\Base\Facades\EmailHandler;
use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Setting\Http\Requests\EmailSendTestRequest;
use Exception;

class EmailTestController extends BaseController
{
    public function __invoke(EmailSendTestRequest $request)
    {
        try {
            $content = file_get_contents(core_path('setting/resources/email-templates/test.tpl'));

            if ($template = $request->input('template')) {
                [$type, $module, $template] = explode('.', $template);

                if ($type && $module && $template) {
                    $content = get_setting_email_template_content($type, $module, $template);
                }
            }

            EmailHandler::send(
                $content,
                'Test',
                $request->input('email'),
                [],
                true
            );

            return $this
                ->httpResponse()
                ->setMessage(trans('core/setting::setting.test_email_send_success'));
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
