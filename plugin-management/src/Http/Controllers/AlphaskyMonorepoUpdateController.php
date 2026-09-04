<?php

namespace Alphasky\PluginManagement\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\PluginManagement\Services\AlphaskyMonorepoUpdater;
use Throwable;

class AlphaskyMonorepoUpdateController extends BaseController
{
    public function __construct(protected AlphaskyMonorepoUpdater $updater)
    {
    }

    public function update(): BaseHttpResponse
    {
        try {
            $result = $this->updater->checkAndUpdate();

            if ($result['updated'] ?? false) {
                return $this
                    ->httpResponse()
                    ->setMessage(trans('packages/plugin-management::plugin.alphasky_monorepo.update_success', [
                        'version' => $result['current_version'] ?: $result['remote_version'],
                    ]));
            }

            return $this
                ->httpResponse()
                ->setMessage(trans('packages/plugin-management::plugin.alphasky_monorepo.no_update', [
                    'version' => $result['local_version'] ?: trans('packages/plugin-management::plugin.alphasky_monorepo.unknown_version'),
                ]));
        } catch (Throwable $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
