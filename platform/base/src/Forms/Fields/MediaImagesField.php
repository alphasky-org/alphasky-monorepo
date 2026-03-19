<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Forms\FormField;

class MediaImagesField extends FormField
{
    protected function getTemplate(): string
    {
        Assets::addScripts(['jquery-ui']);

        return 'core/base::forms.fields.media-images';
    }
}
