<?php

namespace Alphasky\Table\BulkActions;

use Alphasky\Base\Contracts\BaseModel;
use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Exceptions\DisabledInDemoModeException;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Table\Abstracts\TableBulkActionAbstract;
use Illuminate\Database\Eloquent\Model;

class DeleteBulkAction extends TableBulkActionAbstract
{
    protected bool $silent = false;

    public function __construct()
    {
        $this
            ->label(trans('core/table::table.delete'))
            ->confirmationModalButton(trans('core/table::table.delete'))
            ->beforeDispatch(function (): void {
                if (BaseHelper::hasDemoModeEnabled()) {
                    throw new DisabledInDemoModeException();
                }
            });
    }

    public function silent(bool $silent = true): static
    {
        $this->silent = $silent;

        return $this;
    }

    public function dispatch(BaseModel|Model $model, array $ids): BaseHttpResponse
    {
        $model->newQuery()->whereKey($ids)->each(function (BaseModel|Model $item): void {
            $item->delete();

            if (! $this->silent) {
                DeletedContentEvent::dispatch($item::class, request(), $item);
            }
        });

        return BaseHttpResponse::make()
            ->withDeletedSuccessMessage();
    }
}
