<?php

namespace Alphasky\ACL\Http\Controllers\Auth;

use Alphasky\ACL\Forms\Auth\ResetPasswordForm;
use Alphasky\ACL\Traits\ResetsPasswords;
use Alphasky\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class ResetPasswordController extends BaseController
{
    use ResetsPasswords;

    protected string $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');

        $this->redirectTo = route('dashboard.index');
    }

    public function showResetForm(Request $request, $token = null)
    {
        $this->pageTitle(trans('core/acl::auth.reset.title'));

        return ResetPasswordForm::create()->renderForm();
    }
}
