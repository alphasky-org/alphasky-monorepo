<?php
namespace Alphasky\Base\Http\Controllers;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Base\Services\CleanDatabaseService;
use Alphasky\Base\Supports\MembershipAuthorization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemController extends BaseSystemController
{
    public function getIndex(): View
    {
        $this->pageTitle(trans('core/base::base.panel.platform_administration'));

        return view('core/base::system.index');
    }

    public function postAuthorize(MembershipAuthorization $authorization): BaseHttpResponse
    {
        $authorization->authorize();

        return $this->httpResponse();
    }

    public function getMenuItemsCount(): BaseHttpResponse
    {
        $data = apply_filters(BASE_FILTER_MENU_ITEMS_COUNT, []);

        return $this
            ->httpResponse()
            ->setData($data);
    }

    public function getCleanup(
        Request $request,
        CleanDatabaseService $cleanDatabaseService
    ) {
        $this->pageTitle(trans('core/base::system.cleanup.title'));

        Assets::addScriptsDirectly('vendor/core/core/base/js/cleanup.js');

        try {
            $tables = array_map(function (array $table) {
                return $table['name'];
            }, Schema::getTables(Schema::getConnection()->getDatabaseName()));

        } catch (Throwable) {
            $tables = [];
        }

        $disabledTables = [
            'disabled' => $cleanDatabaseService->getIgnoreTables(),
            'checked'  => [],
        ];

        if ($request->isMethod('POST')) {
            if (! config('core.base.general.enabled_cleanup_database', false)) {
                return $this
                    ->httpResponse()
                    ->setCode(401)
                    ->setError()
                    ->setMessage(strip_tags(trans('core/base::system.cleanup.not_enabled_yet')));
            }

            $request->validate(['tables' => ['array']]);

            $cleanDatabaseService->execute($request->input('tables', []));

            return $this
                ->httpResponse()
                ->setMessage(trans('core/base::system.cleanup.success_message'));
        }

        return view('core/base::system.cleanup', compact('tables', 'disabledTables'));
    }

}
