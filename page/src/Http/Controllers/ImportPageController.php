<?php

namespace Alphasky\Page\Http\Controllers;

use Alphasky\DataSynchronize\Http\Controllers\ImportController;
use Alphasky\DataSynchronize\Importer\Importer;
use Alphasky\Page\Importers\PageImporter;

class ImportPageController extends ImportController
{
    protected function getImporter(): Importer
    {
        return PageImporter::make();
    }
}
