<?php

namespace Alphasky\Page\Forms;

use Alphasky\Base\Forms\FieldOptions\ContentFieldOption;
use Alphasky\Base\Forms\FieldOptions\DescriptionFieldOption;
use Alphasky\Base\Forms\FieldOptions\MediaImageFieldOption;
use Alphasky\Base\Forms\FieldOptions\NameFieldOption;
use Alphasky\Base\Forms\FieldOptions\SelectFieldOption;
use Alphasky\Base\Forms\FieldOptions\StatusFieldOption;
use Alphasky\Base\Forms\Fields\EditorField;
use Alphasky\Base\Forms\Fields\MediaImageField;
use Alphasky\Base\Forms\Fields\SelectField;
use Alphasky\Base\Forms\Fields\TextareaField;
use Alphasky\Base\Forms\Fields\TextField;
use Alphasky\Base\Forms\FormAbstract;
use Alphasky\Page\Http\Requests\PageRequest;
use Alphasky\Page\Models\Page;
use Alphasky\Page\Supports\Template;

class PageForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(Page::class)
            ->setValidatorClass(PageRequest::class)
            ->add('name', TextField::class, NameFieldOption::make()->maxLength(120)->required())
            ->add('description', TextareaField::class, DescriptionFieldOption::make())
            ->add('content', EditorField::class, ContentFieldOption::make()->allowedShortcodes())
            ->add('status', SelectField::class, StatusFieldOption::make())
            ->when(Template::getPageTemplates(), function (PageForm $form, array $templates) {
                return $form
                    ->add(
                        'template',
                        SelectField::class,
                        SelectFieldOption::make()
                            ->label(trans('core/base::forms.template'))
                            ->required()
                            ->choices($templates)
                    );
            })
            ->add('image', MediaImageField::class, MediaImageFieldOption::make())
            ->setBreakFieldPoint('status');
    }
}
