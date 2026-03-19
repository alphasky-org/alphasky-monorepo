<?php

namespace Alphasky\JsValidation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Alphasky\JsValidation\Javascript\JavascriptValidator make(array $rules, array $messages = [], array $customAttributes = [], string|null $selector = null)
 * @method static \Alphasky\JsValidation\Javascript\JavascriptValidator formRequest($formRequest, $selector = null)
 * @method static \Alphasky\JsValidation\Javascript\JavascriptValidator validator(\Illuminate\Validation\Validator $validator, string|null $selector = null)
 *
 * @see \Alphasky\JsValidation\JsValidatorFactory
 */
class JsValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'js-validator';
    }
}
