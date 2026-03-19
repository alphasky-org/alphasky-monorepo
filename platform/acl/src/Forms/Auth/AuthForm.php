<?php

namespace Alphasky\ACL\Forms\Auth;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Forms\FieldOptions\HtmlFieldOption;
use Alphasky\Base\Forms\Fields\HtmlField;
use Alphasky\Base\Forms\FormAbstract;
use Alphasky\Base\Models\BaseModel;

class AuthForm extends FormAbstract
{
    public function setup(): void
    {
        Assets::addScripts('form-validation')
            ->removeStyles([
                'select2',
                'fancybox',
                'spectrum',
                'custom-scrollbar',
                'datepicker',
                'fontawesome',
                'toastr',
            ])
            ->removeScripts([
                'select2',
                'fancybox',
                'cookie',
                'spectrum',
                'toastr',
                'modernizr',
                'excanvas',
                'jquery-waypoints',
                'stickytableheaders',
                'ie8-fix',
            ]);

        $this
            ->model(BaseModel::class)
            ->template('core/acl::auth.form');
    }

    public function heading(string $heading): self
    {
        $this->add(
            'heading',
            HtmlField::class,
            HtmlFieldOption::make()
                ->content(sprintf(
                    '<h2 class="h3 text-center mb-3">%s</h2>',
                    $heading
                ))
        );

        return $this;
    }

    public function submitButton(string $label, ?string $icon = null): self
    {
        $this
            ->add(
                'open_wrap_button',
                HtmlField::class,
                HtmlFieldOption::make()->content('<div class="form-footer">')
            )
            ->add(
                'submit',
                HtmlField::class,
                HtmlFieldOption::make()->view('core/acl::auth.includes.submit', compact('label', 'icon'))
            )
            ->add(
                'close_wrap_button',
                HtmlField::class,
                HtmlFieldOption::make()->content('</div>')
            );

        return $this;
    }
}
