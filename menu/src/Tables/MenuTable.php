<?php

namespace Alphasky\Menu\Tables;

use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Menu\Facades\Menu as MenuFacade;
use Alphasky\Menu\Models\Menu;
use Alphasky\Menu\Models\MenuLocation;
use Alphasky\Table\Abstracts\TableAbstract;
use Alphasky\Table\Actions\DeleteAction;
use Alphasky\Table\Actions\EditAction;
use Alphasky\Table\BulkActions\DeleteBulkAction;
use Alphasky\Table\BulkChanges\CreatedAtBulkChange;
use Alphasky\Table\BulkChanges\NameBulkChange;
use Alphasky\Table\BulkChanges\StatusBulkChange;
use Alphasky\Table\Columns\CreatedAtColumn;
use Alphasky\Table\Columns\FormattedColumn;
use Alphasky\Table\Columns\IdColumn;
use Alphasky\Table\Columns\NameColumn;
use Alphasky\Table\Columns\StatusColumn;
use Alphasky\Table\HeaderActions\CreateHeaderAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class MenuTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Menu::class)
            ->addColumns([
                IdColumn::make(),
                NameColumn::make()->route('menus.edit'),
                FormattedColumn::make('locations_display')
                    ->label(trans('packages/menu::menu.locations'))
                    ->orderable(false)
                    ->searchable(false)
                    ->getValueUsing(function (FormattedColumn $column) {
                        $locations = $column
                            ->getItem()
                            ->locations
                            ->sortBy('name')
                            ->map(function (MenuLocation $location) {
                                $locationName = Arr::get(MenuFacade::getMenuLocations(), $location->location);

                                if (! $locationName) {
                                    return null;
                                }

                                return BaseHelper::renderBadge($locationName, 'info', ['class' => 'me-1']);
                            })
                            ->all();

                        return implode(', ', $locations);
                    })
                    ->withEmptyState(),
                FormattedColumn::make('items_count')
                    ->label(trans('packages/menu::menu.items'))
                    ->orderable(false)
                    ->searchable(false)
                    ->getValueUsing(function (FormattedColumn $column) {
                        return BaseHelper::renderIcon('ti ti-link') . ' '
                            . number_format($column->getItem()->menu_nodes_count);
                    }),
                CreatedAtColumn::make(),
                StatusColumn::make(),
            ])
            ->addHeaderAction(CreateHeaderAction::make()->route('menus.create'))
            ->addActions([
                EditAction::make()->route('menus.edit'),
                DeleteAction::make()->route('menus.destroy'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('menus.destroy'))
            ->addBulkChanges([
                NameBulkChange::make(),
                StatusBulkChange::make(),
                CreatedAtBulkChange::make(),
            ])
            ->queryUsing(function (Builder $query): void {
                $query
                    ->select([
                        'id',
                        'name',
                        'created_at',
                        'status',
                    ])
                    ->with('locations')
                    ->withCount('menuNodes');
            });
    }
}
