<?php

namespace Alphasky\JsValidation\Providers;

use Alphasky\Base\Supports\ServiceProvider;
use Alphasky\Base\Traits\LoadAndPublishDataTrait;
use Alphasky\JsValidation\Javascript\ValidatorHandler;
use Alphasky\JsValidation\JsValidatorFactory;
use Illuminate\Contracts\Http\Kernel;

class JsValidationServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this
            ->setNamespace('core/js-validation')
            ->loadAndPublishConfigurations(['js-validation'])
            ->loadAndPublishViews()
            ->publishAssets();

        $this->bootstrapValidator();

    
    }

    protected function bootstrapValidator(): void
    {
        $callback = function () {
            return true;
        };

        $this->app['validator']->extend(ValidatorHandler::JS_VALIDATION_DISABLE, $callback);
    }

    public function register(): void
    {
        $this->app->singleton('js-validator', function ($app) {
            $config = $app['config']->get('core.js-validation.js-validation');

            return new JsValidatorFactory($app, $config);
        });
    }
}
