<?php
namespace Alphasky\Base\Http\Controllers;

use Alphasky\Base\Facades\Assets;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Alphasky\Base\Services\CleanDatabaseService;
use Alphasky\Base\Supports\MembershipAuthorization;
use Alphasky\PluginManagement\Services\PluginService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
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


    public function getAlphasky(Request $request)
    {
        if (session()->isStarted()) {
            session()->save();
        }

        set_time_limit(6000);

        $userInput = trim((string) ($request->query('userInput', $request->input('userInput', ''))));
        $menuKey = trim((string) ($request->query('key', $request->input('key', ''))));
        $conversationToken = trim((string) ($request->query('conversation_token', $request->input('conversation_token', ''))));
        $conversationToken = $conversationToken !== '' ? $conversationToken : (string) Str::uuid();
        $surveysId = (int) ($request->query('surveys_id', $request->input('surveys_id', 0)));
        $alphaskyKey = trim((string) ($request->query('alphasky_key', $request->input('alphasky_key', ''))));
        $requestDomain = trim((string) ($request->query('domain', $request->input('domain', $request->getHost()))));
        $upstreamBaseUrl = rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');
        $upstreamUrl = $upstreamBaseUrl . '/api/v1/prompt';
        
        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($userInput, $menuKey, $conversationToken, $surveysId, $alphaskyKey, $requestDomain, $upstreamBaseUrl, $upstreamUrl) {
            $send = function (string $message, array $extra = []) use ($conversationToken) {
                echo 'data: ' . json_encode(array_merge(['message' => $message, 'conversation_token' => $conversationToken], $extra), JSON_UNESCAPED_UNICODE) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            if ($userInput === '') {
                $send(__('core/base::system.alphasky_copilot.empty_prompt'));
                $send(__('core/base::system.alphasky_copilot.task_completed'));

                return;
            }

            if ($upstreamBaseUrl === '') {
                $send(__('core/base::system.alphasky_copilot.missing_upstream_url'));
                $send(__('core/base::system.alphasky_copilot.task_completed'));

                return;
            }

            try {
                $upstreamResponse = Http::withHeaders([
                    'Accept' => 'text/event-stream',
                    'X-API-KEY' => 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
                ])->timeout(0)
                    ->withOptions([
                        'stream'      => true,
                        'read_timeout' => 120,
                    ])
                    ->asForm()
                    ->post($upstreamUrl, [
                        'userInput' => $userInput,
                        'key'       => $menuKey,
                        'surveys_id' => $surveysId,
                        'conversation_token' => $conversationToken,

                        'alphasky_key' => $alphaskyKey,
                        'domain' => $requestDomain,
                    ]);

                if (! $upstreamResponse->successful()) {
                    $send(__('core/base::system.alphasky_copilot.upstream_connection_failed'), [
                        'status' => $upstreamResponse->status(),
                        'body'   => $upstreamResponse->body(),
                    ]);
                    $send(__('core/base::system.alphasky_copilot.task_completed'));

                    return;
                }

                $stream = $upstreamResponse->toPsrResponse()->getBody();
                $buffer = '';

                while (! $stream->eof()) {
                    $chunk = $stream->read(1);

                    if ($chunk === '') {
                        continue;
                    }

                    $buffer .= str_replace(["\r\n", "\r"], "\n", $chunk);

                    while (($eventEnd = strpos($buffer, "\n\n")) !== false) {
                        $event = substr($buffer, 0, $eventEnd);
                        $buffer = substr($buffer, $eventEnd + 2);
                        $payload = $this->decodeServerSentEvent($event);

                        if (! is_array($payload)) {
                            continue;
                        }

                        $message = (string) ($payload['message'] ?? '');
                        unset($payload['message']);

                        if (isset($payload['survey_id'])) {
                            $surveysId = (int) $payload['survey_id'];
                        }

                        $send($message, array_merge($payload, ['surveys_id' => $surveysId]));
                    }
                }
            } catch (Throwable $exception) {
                $send(__('core/base::system.alphasky_copilot.execution_error', ['message' => $exception->getMessage()]));
                $send(__('core/base::system.alphasky_copilot.task_completed'));
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Nginx Buffering Fix
        ]);
    }

    public function postAlphaskyAnswer(Request $request)
    {
        $upstreamBaseUrl = rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');

        if ($upstreamBaseUrl === '') {
            return response()->json([
                'message' => __('core/base::system.alphasky_copilot.missing_upstream_url'),
                'error' => 'missing_upstream_url',
            ], 422);
        }

        $upstreamResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
        ])->asJson()->post($upstreamBaseUrl . '/api/v1/prompt-answer', [
            'request_id' => $request->input('request_id'),
            'answer' => $request->input('answer'),
        ]);

        return response($upstreamResponse->body(), $upstreamResponse->status())
            ->header('Content-Type', $upstreamResponse->header('Content-Type', 'application/json'));
    }

    public function getAlphaskyConversations(Request $request)
    {
        $upstreamBaseUrl = rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');
        $alphaskyKey = trim((string) ($request->query('alphasky_key', $request->input('alphasky_key', ''))));
        $requestDomain = trim((string) ($request->query('domain', $request->input('domain', $request->getHost()))));

        if ($upstreamBaseUrl === '' || $alphaskyKey === '') {
            return response()->json(['data' => []]);
        }

        $upstreamResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
        ])->post($upstreamBaseUrl . '/api/v1/alphasky-conversations', [
            'alphasky_key' => $alphaskyKey,
            'domain' => $requestDomain,
        ]);

        return response($upstreamResponse->body(), $upstreamResponse->status())
            ->header('Content-Type', $upstreamResponse->header('Content-Type', 'application/json'));
    }

    public function getAlphaskyConversation(Request $request)
    {
        $upstreamBaseUrl = rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');
        $token = trim((string) ($request->query('token', $request->input('token', ''))));
        $alphaskyKey = trim((string) ($request->query('alphasky_key', $request->input('alphasky_key', ''))));
        $requestDomain = trim((string) ($request->query('domain', $request->input('domain', $request->getHost()))));

        if ($upstreamBaseUrl === '' || $token === '' || $alphaskyKey === '') {
            return response()->json(['data' => []]);
        }

        $upstreamResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
        ])->post($upstreamBaseUrl . '/api/v1/alphasky-conversation', [
            'token' => $token,
            'alphasky_key' => $alphaskyKey,
            'domain' => $requestDomain,
        ]);

        return response($upstreamResponse->body(), $upstreamResponse->status())
            ->header('Content-Type', $upstreamResponse->header('Content-Type', 'application/json'));
    }

    public function postAlphaskyConversationMessage(Request $request)
    {
        $upstreamBaseUrl = rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');
        $action = trim((string) $request->input('action', ''));
        $alphaskyKey = trim((string) $request->input('alphasky_key', ''));
        $requestDomain = trim((string) ($request->input('domain', $request->getHost())));

        if ($upstreamBaseUrl === '' || $alphaskyKey === '' || ! in_array($action, ['update', 'delete'], true)) {
            return response()->json(['data' => []], 422);
        }

        $upstreamResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
        ])->post($upstreamBaseUrl . '/api/v1/alphasky-conversation-message/' . $action, [
            'id' => (int) $request->input('id', 0),
            'token' => trim((string) $request->input('token', '')),
            'chat' => (string) $request->input('chat', ''),
            'alphasky_key' => $alphaskyKey,
            'domain' => $requestDomain,
        ]);

        return response($upstreamResponse->body(), $upstreamResponse->status())
            ->header('Content-Type', $upstreamResponse->header('Content-Type', 'application/json'));
    }

    public function postAlphaskyInstallPlugin(Request $request)
    {
        $request->validate([
            'survey_id' => ['required', 'integer'],
            'module_name' => ['required', 'string'],
            'path' => ['required', 'string'],
        ]);

        $moduleName = basename(trim((string) $request->input('module_name')));
        $upstreamBaseUrl = rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');

        if ($upstreamBaseUrl === '') {
            return response()->json([
                'message' => __('core/base::system.alphasky_copilot.missing_upstream_url'),
                'error' => 'missing_upstream_url',
            ], 422);
        }

        $packagePath = '/' . ltrim((string) $request->input('path'), '/');
        $tmpPath = storage_path('app/tmp');

        if (! is_dir($tmpPath)) {
            mkdir($tmpPath, 0755, true);
        }

        $localZipPath = $tmpPath . '/' . (int) $request->input('survey_id') . '-' . $moduleName . '.zip';
        $packageResponse = Http::withHeaders([
            'X-API-KEY' => 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
        ])->timeout(300)->post($upstreamBaseUrl . $packagePath);

        if (! $packageResponse->successful()) {
            return response()->json([
                'message' => __('core/base::system.alphasky_copilot.plugin_download_failed'),
                'status' => $packageResponse->status(),
                'error' => 'download_failed',
            ], 422);
        }

        file_put_contents($localZipPath, $packageResponse->body());

        $zip = new \ZipArchive();

        if ($zip->open($localZipPath) !== true) {
            return response()->json([
                'message' => __('core/base::system.alphasky_copilot.invalid_plugin_zip'),
                'error' => 'invalid_zip',
            ], 422);
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = (string) $zip->getNameIndex($index);

            if (! str_starts_with($entryName, $moduleName . '/') || str_contains($entryName, '../')) {
                $zip->close();

                return response()->json([
                    'message' => __('core/base::system.alphasky_copilot.unsafe_plugin_zip'),
                    'error' => 'unsafe_zip',
                ], 422);
            }
        }

        $pluginsPath = base_path('platform/plugins');

        if (! is_dir($pluginsPath)) {
            mkdir($pluginsPath, 0755, true);
        }

        $zip->extractTo($pluginsPath);
        $zip->close();

        $publicPath = base_path('platform/plugins/' . $moduleName . '/public');

        if (! is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        $activatedPlugins = PluginService::getActivatedPlugins();
        $isActive = in_array($moduleName, $activatedPlugins, true);

        return response()->json([
            'message' => __('core/base::system.alphasky_copilot.plugin_installed_successfully'),
            'module_name' => $moduleName,
            'is_active' => $isActive,
            'question' => $isActive
                ? __('core/base::system.alphasky_copilot.plugin_active_update_question')
                : __('core/base::system.alphasky_copilot.plugin_inactive_activate_question'),
        ]);
    }

    public function postAlphaskyApplyPlugin(Request $request)
    {
        $request->validate([
            'module_name' => ['required', 'string'],
        ]);

        $moduleName = basename(trim((string) $request->input('module_name')));
        $migrationPath = 'platform/plugins/' . $moduleName . '/database/migrations';
        $commands = [];

        $publishResult = $this->runAlphaskyArtisanCommand(['cms:publish:assets']);
        $commands['php artisan cms:publish:assets'] = $publishResult['output'];

        if (! $publishResult['successful']) {
            return response()->json([
                'message' => $publishResult['output'],
                'module_name' => $moduleName,
                'commands' => $commands,
                'error' => 'publish_assets_failed',
            ], 422);
        }

        $activateResult = $this->runAlphaskyArtisanCommand(['cms:plugin:activate', $moduleName]);
        $commands['php artisan cms:plugin:activate ' . $moduleName] = $activateResult['output'];
        $isPluginActive = in_array($moduleName, PluginService::getActivatedPlugins(), true);

        if (! $activateResult['successful'] && ! $isPluginActive) {
            return response()->json([
                'message' => $activateResult['output'],
                'module_name' => $moduleName,
                'commands' => $commands,
                'error' => 'plugin_activate_failed',
            ], 422);
        }

        if (is_dir(base_path($migrationPath))) {
            $migrationResult = $this->runAlphaskyArtisanCommand(['migrate', '--force', '--path=' . $migrationPath]);
            $commands['php artisan migrate --force --path=' . $migrationPath] = $migrationResult['output'];

            if (! $migrationResult['successful']) {
                return response()->json([
                    'message' => $migrationResult['output'],
                    'module_name' => $moduleName,
                    'commands' => $commands,
                    'error' => 'plugin_migration_failed',
                ], 422);
            }
        } else {
            $commands['php artisan migrate --force --path=' . $migrationPath] = __('core/base::system.alphasky_copilot.no_plugin_migrations');
        }

        return response()->json([
            'message' => __('core/base::system.alphasky_copilot.plugin_commands_success'),
            'module_name' => $moduleName,
            'commands' => $commands,
        ]);
    }

    public function postAlphaskyPluginCommand(Request $request)
    {
        $request->validate([
            'module_name' => ['required', 'string'],
            'action' => ['required', 'string', 'in:activate,deactivate,delete,remove'],
            'drop_tables' => ['sometimes', 'array'],
            'ignore_missing' => ['sometimes', 'boolean'],
        ]);

        $moduleName = basename(trim((string) $request->input('module_name')));
        $action = (string) $request->input('action');
        $ignoreMissing = $request->boolean('ignore_missing');
        $commands = [];

        if ($action === 'activate') {
            return $this->postAlphaskyApplyPlugin($request);
        }

        if (in_array($action, ['delete', 'remove'], true)) {
            $this->dropAlphaskyPluginTables((array) $request->input('drop_tables', []));

            if ($ignoreMissing && ! is_dir(plugin_path($moduleName))) {
                return response()->json([
                    'message' => __('core/base::system.alphasky_copilot.plugin_commands_success'),
                    'module_name' => $moduleName,
                    'action' => $action,
                    'commands' => $commands,
                ]);
            }
        }

        $artisanArguments = match ($action) {
            'deactivate' => ['cms:plugin:deactivate', $moduleName],
            'delete', 'remove' => ['cms:plugin:remove', $moduleName, '--force'],
        };

        $result = $this->runAlphaskyArtisanCommand($artisanArguments);
        $commands['php artisan ' . implode(' ', $artisanArguments)] = $result['output'];

        if (! $result['successful']) {
            return response()->json([
                'message' => $result['output'],
                'module_name' => $moduleName,
                'action' => $action,
                'commands' => $commands,
                'error' => 'plugin_command_failed',
            ], 422);
        }

        return response()->json([
            'message' => __('core/base::system.alphasky_copilot.plugin_commands_success'),
            'module_name' => $moduleName,
            'action' => $action,
            'commands' => $commands,
        ]);
    }

    protected function dropAlphaskyPluginTables(array $tables): void
    {
        $tables = array_values(array_unique(array_filter(array_map(
            fn ($table) => trim((string) $table),
            $tables
        ))));

        if ($tables === []) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                    continue;
                }

                Schema::dropIfExists($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    protected function runAlphaskyArtisanCommand(array $arguments): array
    {
        $process = new Process(array_merge(['php', 'artisan'], $arguments));
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300);
        $process->run();

        $output = trim($process->getOutput() . PHP_EOL . $process->getErrorOutput());

        return [
            'successful' => $process->isSuccessful(),
            'output' => $output !== '' ? $output : implode(' ', array_merge(['php', 'artisan'], $arguments)),
        ];
    }

    protected function resolveAlphaskyUpstreamBaseUrl(): string
    {
        return rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/');
    }

    protected function decodeServerSentEvent(string $event): ?array
    {
        $dataLines = [];

        foreach (preg_split('/\r\n|\r|\n/', $event) as $line) {
            if (str_starts_with($line, 'data:')) {
                $dataLines[] = ltrim(substr($line, 5));
            }
        }

        if ($dataLines === []) {
            return null;
        }

        $payload = json_decode(implode("\n", $dataLines), true);

        return is_array($payload) ? $this->normalizeUtf8Payload($payload) : null;
    }

    protected function normalizeUtf8Payload(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeUtf8Payload($item), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        if (! preg_match('/[ØÙ]/u', $value)) {
            return $value;
        }

        $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');

        return is_string($converted) && $converted !== '' ? $converted : $value;
    }


}
