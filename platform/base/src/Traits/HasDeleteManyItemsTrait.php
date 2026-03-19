<?php

namespace Alphasky\Base\Traits;

use Alphasky\Base\Events\DeletedContentEvent;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Base\Models\BaseModel;
use Alphasky\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Http\Request;

/**
 * @deprecated since v6.8.0
 */
trait HasDeleteManyItemsTrait
{
    protected function executeDeleteItems(
        Request $request,
        BaseHttpResponse $response,
        RepositoryInterface|BaseModel $repository
    ): BaseHttpResponse {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return $response
                ->setError()
                ->setMessage(trans('core/base::notices.no_select'));
        }

        foreach ($ids as $id) {
            $item = $repository->findOrFail($id);
            if (! $item) {
                continue;
            }

            $item->delete();

            DeletedContentEvent::dispatch($item::class, $request, $item);
        }

        return $response->withDeletedSuccessMessage();
    }
}
