<?php

namespace Alphasky\Media\Events;

use Alphasky\Media\Models\MediaFolder;
use Illuminate\Foundation\Events\Dispatchable;

class MediaFolderRenamed
{
    use Dispatchable;

    public function __construct(public MediaFolder $folder)
    {
    }
}
