<?php

namespace Alphasky\Theme\Listeners;

use Alphasky\Base\Events\FormRendering;
use Alphasky\Base\Facades\AdminHelper;
use Alphasky\JsValidation\Facades\JsValidator;
use Alphasky\Theme\Facades\Theme;
use Illuminate\Support\Str;

class AddFormJsValidation
{
    public function handle(FormRendering $event): void
    {
        if (AdminHelper::isInAdmin()) {
            return;
        }

        $form = $event->form;

        if (! $form->getValidatorClass()) {
            return;
        }

        Theme::asset()
            ->container('footer')
            ->usePath(false)
            ->add('js-validation', 'vendor/core/core/js-validation/js/js-validation.js', ['jquery'], version: '1.0.1');

        $formSelector = $form->getDomSelector();

        Theme::asset()
            ->container('footer')
            ->writeContent(
                'js-validation-form-rules-' . Str::slug($formSelector),
                JsValidator::formRequest($form->getValidatorClass(), $formSelector)->render(),
                ['jquery']
            );
    }
}
