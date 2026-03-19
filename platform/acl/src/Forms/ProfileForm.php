<?php

namespace Alphasky\ACL\Forms;

use Alphasky\ACL\Http\Requests\UpdateProfileRequest;
use Alphasky\ACL\Models\User;
use Alphasky\Base\Forms\FieldOptions\EmailFieldOption;
use Alphasky\Base\Forms\FieldOptions\TextFieldOption;
use Alphasky\Base\Forms\Fields\TextField;
use Alphasky\Base\Forms\FormAbstract;

class ProfileForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(User::class)
            ->template('core/base::forms.form-no-wrap')
            ->setFormOption('id', 'profile-form')
            ->setValidatorClass(UpdateProfileRequest::class)
            ->setMethod('PUT')
            ->columns()
            ->add(
                'first_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.info.first_name'))
                    ->placeholder(trans('core/acl::users.first_name_placeholder'))
                    ->required()
                    ->maxLength(30)
            )
            ->add(
                'last_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.info.last_name'))
                    ->placeholder(trans('core/acl::users.last_name_placeholder'))
                    ->required()
                    ->maxLength(30)
            )
            ->add(
                'username',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.username'))
                    ->placeholder(trans('core/acl::users.username_placeholder'))
                    ->required()
                    ->maxLength(30)
            )
            ->add('email', TextField::class, EmailFieldOption::make()->required()->placeholder(trans('core/acl::users.email_placeholder')))
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/acl::users.phone'))
                    ->placeholder(trans('core/acl::users.phone_placeholder'))
                    ->maxLength(20)
            )
            ->setActionButtons(view('core/acl::users.profile.actions')->render());
    }
}
