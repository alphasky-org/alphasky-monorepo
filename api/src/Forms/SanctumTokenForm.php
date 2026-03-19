<?php

namespace Alphasky\Api\Forms;

use Alphasky\Api\Http\Requests\StoreSanctumTokenRequest;
use Alphasky\Api\Models\PersonalAccessToken;
use Alphasky\Base\Forms\FieldOptions\NameFieldOption;
use Alphasky\Base\Forms\Fields\TextField;
use Alphasky\Base\Forms\FormAbstract;

class SanctumTokenForm extends FormAbstract
{
    public function buildForm(): void
    {
        $this
            ->setupModel(new PersonalAccessToken())
            ->setValidatorClass(StoreSanctumTokenRequest::class)
            ->add('name', TextField::class, NameFieldOption::make()->toArray());
    }
}
