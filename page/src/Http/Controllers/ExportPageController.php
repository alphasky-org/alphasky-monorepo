<?php

namespace Alphasky\Page\Http\Controllers;

use Alphasky\Base\Enums\BaseStatusEnum;
use Alphasky\DataSynchronize\Exporter\Exporter;
use Alphasky\DataSynchronize\Http\Controllers\ExportController;
use Alphasky\DataSynchronize\Http\Requests\ExportRequest;
use Alphasky\Page\Exporters\PageExporter;

class ExportPageController extends ExportController
{
    protected function getExporter(): Exporter
    {
        $exporter = PageExporter::make();

        if (request()->has('limit')) {
            $exporter->setLimit((int) request()->input('limit'));
        }

        if (request()->has('status') && request()->input('status') !== '') {
            $exporter->setStatus(request()->input('status'));
        }

        if (request()->has('template') && request()->input('template') !== '') {
            $exporter->setTemplate(request()->input('template'));
        }

        if (request()->has(['start_date', 'end_date'])) {
            $exporter->setDateRange(
                request()->input('start_date'),
                request()->input('end_date')
            );
        }

        return $exporter;
    }

    public function store(ExportRequest $request)
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:' . implode(',', BaseStatusEnum::values())],
            'template' => ['nullable', 'string', 'max:60'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return parent::store($request);
    }
}
