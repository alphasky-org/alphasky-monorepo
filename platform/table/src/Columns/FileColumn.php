<?php

namespace Alphasky\Table\Columns;

use Alphasky\Base\Facades\Html;
use Alphasky\Media\Facades\RvMedia;
use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;

class FileColumn extends FormattedColumn implements FormattedColumnContract
{
    protected bool $relative = false;

    protected int $width = 50;

    protected ?string $mediaSize = 'thumb';

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'file', $name)
            ->title(trans('core/base::tables.file'))
            ->orderable(false)
            ->searchable(false)
            ->width(50);
    }

    public function relative(bool $flag = true): static
    {
        $this->relative = $flag;

        return $this;
    }

    public function with(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function mediaSize(?string $mediaSize): static
    {
        $this->mediaSize = $mediaSize;

        return $this;
    }

    public function fullMediaSize(): static
    {
        return $this->mediaSize(null);
    }

    public function formattedValue($value): string
    {
        $table = $this->getTable();

        if ($table->request()->has('action')) {
            if ($table->isExportingToCSV()) {
                return $this->getFileUrl($value, null);
            }

            if ($table->isExportingToExcel()) {
                return $this->getFileUrl($value);
            }
        }

        return Html::link(
            $this->getFileUrl($value),
           '',
            [ 'target' => '_blank' , 'class' => 'fa fa-file', 'style' => 'font-size: 20px;', 'title' => trans('core/base::tables.file')]
            
        )->toHtml();
    }

    protected function getFileUrl(?string $value): string
    {
        return (string) RvMedia::getImageUrl($value);
    }
}
