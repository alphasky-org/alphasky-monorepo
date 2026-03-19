<?php

namespace Alphasky\Api\Tables;

use Alphasky\Api\Models\PersonalAccessToken;
use Alphasky\Table\Abstracts\TableAbstract;
use Alphasky\Table\Actions\DeleteAction;
use Alphasky\Table\BulkActions\DeleteBulkAction;
use Alphasky\Table\Columns\Column;
use Alphasky\Table\Columns\CreatedAtColumn;
use Alphasky\Table\Columns\DateTimeColumn;
use Alphasky\Table\Columns\IdColumn;
use Alphasky\Table\Columns\NameColumn;
use Alphasky\Table\HeaderActions\CreateHeaderAction;
use Illuminate\Database\Eloquent\Builder;

class SanctumTokenTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->setView('packages/api::table')
            ->model(PersonalAccessToken::class)
            ->addHeaderAction(CreateHeaderAction::make()->route('api.sanctum-token.create'))
            ->addAction(DeleteAction::make()->route('api.sanctum-token.destroy'))
            ->addColumns([
                IdColumn::make(),
                NameColumn::make(),
                Column::make('abilities')
                    ->label(trans('packages/api::sanctum-token.abilities')),
                DateTimeColumn::make('last_used_at')
                    ->label(trans('packages/api::sanctum-token.last_used_at')),
                CreatedAtColumn::make(),
            ])
            ->addBulkAction(DeleteBulkAction::make())
            ->queryUsing(fn (Builder $query) => $query->select([
                'id',
                'name',
                'abilities',
                'last_used_at',
                'created_at',
            ]));
    }
}
