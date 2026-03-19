<?php

namespace Alphasky\Page\Tables;

use Alphasky\Page\Models\Page;
use Alphasky\Table\Abstracts\TableAbstract;
use Alphasky\Table\Actions\DeleteAction;
use Alphasky\Table\Actions\EditAction;
use Alphasky\Table\BulkActions\DeleteBulkAction;
use Alphasky\Table\BulkChanges\CreatedAtBulkChange;
use Alphasky\Table\BulkChanges\NameBulkChange;
use Alphasky\Table\BulkChanges\SelectBulkChange;
use Alphasky\Table\BulkChanges\StatusBulkChange;
use Alphasky\Table\Columns\CreatedAtColumn;
use Alphasky\Table\Columns\FormattedColumn;
use Alphasky\Table\Columns\IdColumn;
use Alphasky\Table\Columns\NameColumn;
use Alphasky\Table\Columns\StatusColumn;
use Alphasky\Table\HeaderActions\CreateHeaderAction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class PageTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Page::class)
            ->addHeaderAction(CreateHeaderAction::make()->route('pages.create'))
            ->addActions([
                EditAction::make()->route('pages.edit'),
                DeleteAction::make()->route('pages.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                NameColumn::make()->route('pages.edit'),
                FormattedColumn::make('template')
                    ->title(trans('core/base::tables.template'))
                    ->alignStart()
                    ->getValueUsing(function (FormattedColumn $column) {
                        static $pageTemplates;

                        $pageTemplates ??= get_page_templates();

                        return Arr::get($pageTemplates, $column->getItem()->template ?: 'default');
                    }),
                CreatedAtColumn::make(),
                StatusColumn::make(),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('pages.destroy'),
            ])
            ->addBulkChanges([
                NameBulkChange::make(),
                SelectBulkChange::make()
                    ->name('template')
                    ->title(trans('core/base::tables.template'))
                    ->choices(fn () => get_page_templates()),
                StatusBulkChange::make(),
                CreatedAtBulkChange::make(),
            ])
            ->queryUsing(function (Builder $query): void {
                $query->select([
                    'id',
                    'name',
                    'template',
                    'created_at',
                    'status',
                ]);
            });
    }
}
