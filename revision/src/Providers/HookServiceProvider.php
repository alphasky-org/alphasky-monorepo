<?php

namespace Alphasky\Revision\Providers;

use Alphasky\Base\Facades\AdminHelper;
use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Forms\FormAbstract;
use Alphasky\Base\Forms\FormTab;
use Alphasky\Base\Models\BaseModel;
use Alphasky\Base\Supports\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FormAbstract::extend(function (FormAbstract $form): void {
            $model = $form->getModel();

            if (
                ! $model instanceof BaseModel
                || ! $model->exists
                || ! $this->isSupported($model)
                || ! AdminHelper::isInAdmin(true)
            ) {
                return;
            }

            Assets::addStylesDirectly('vendor/core/packages/revision/css/revision.css')
                ->addScriptsDirectly([
                    'vendor/core/packages/revision/js/html-diff.js',
                    'vendor/core/packages/revision/js/revision.js',
                ]);

            $form->addTab(
                FormTab::make()
                    ->id('revisions')
                    ->label(trans('core/base::tabs.revision'))
                    ->content(view('packages/revision::history-content', compact('model')))
            );
        }, 999);
    }

    protected function isSupported(string|Model $model): bool
    {
        if (is_object($model)) {
            $model = $model::class;
        }

        return in_array($model, config('packages.revision.general.supported', []));
    }
}
