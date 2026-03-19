<?php
namespace Alphasky\Base\Supports;

use Alphasky\Base\Events\SystemUpdateAvailable;
use Alphasky\Base\Events\SystemUpdateCachesCleared;
use Alphasky\Base\Events\SystemUpdateCachesClearing;
use Alphasky\Base\Events\SystemUpdateChecked;
use Alphasky\Base\Events\SystemUpdateChecking;
use Alphasky\Base\Events\SystemUpdateDBMigrated;
use Alphasky\Base\Events\SystemUpdateDBMigrating;
use Alphasky\Base\Events\SystemUpdatePublished;
use Alphasky\Base\Events\SystemUpdatePublishing;
use Alphasky\Base\Events\SystemUpdateUnavailable;
use Alphasky\Base\Exceptions\MissingZipExtensionException;
use Alphasky\Base\Facades\BaseHelper;
use Alphasky\Base\Services\ClearCacheService;
use Alphasky\Base\Supports\ValueObjects\CoreProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

/**
 * DO NOT MODIFY THIS FILE.
 *
 * @readonly
 */
final class Core
{
    private string $basePath;

    private string $coreDataFilePath;

    private string $version = '1.0.0';

    private string $minimumPhpVersion = '8.2.0';

    protected static array $coreFileData = [];

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly Filesystem $files
    ) {
        $this->basePath = base_path();

        $this->coreDataFilePath = core_path('core.json');

     
    }

    public static function make(): self
    {
        return app(self::class);
    }

    public function version(): string
    {
        return $this->version;
    }

    public function minimumPhpVersion(): string
    {
        return $this->minimumPhpVersion;
    }

    public function checkUpdate(): CoreProduct | false
    {
        SystemUpdateChecking::dispatch();

        $response = $this->createRequest('check_update', [
            'product_id'      => $this->productId,
            'current_version' => $this->version,
        ]);

        SystemUpdateChecked::dispatch();

        $product = $this->parseProductUpdateResponse($response);

        return tap($product, function (CoreProduct | false $coreProduct): void {
            if (! $coreProduct || ! $coreProduct->hasUpdate()) {
                SystemUpdateUnavailable::dispatch();

                return;
            }

            SystemUpdateAvailable::dispatch($coreProduct);
        });
    }

    public function publishUpdateAssets(): void
    {
        $this->publishCoreAssets();
        $this->publishPackagesAssets();
    }

    public function publishCoreAssets(): void
    {
        SystemUpdatePublishing::dispatch();

        $this->publishAssets(core_path());
    }

    public function publishPackagesAssets(): void
    {
        $this->publishAssets(package_path());

        $this->publishAssets(base_path('vendor'));

        SystemUpdatePublished::dispatch();
    }

    public function cleanCaches(): void
    {
        try {
            SystemUpdateCachesClearing::dispatch();

            ClearCacheService::make()->purgeAll();

            SystemUpdateCachesCleared::dispatch();

            self::$coreFileData = [];
        } catch (Throwable $exception) {
            $this->logError($exception);
        }
    }

    public function cleanUp(): void
    {
        $this->cleanCaches();
    }

    public function logError(Exception | Throwable $exception): void
    {
        BaseHelper::logError($exception);
    }

    private function publishPaths(): array
    {
        return IlluminateServiceProvider::pathsToPublish(null, 'cms-public');
    }

    public function publishAssets(string $path): void
    {
        foreach ($this->publishPaths() as $from => $to) {
            if (! Str::contains($from, $path)) {
                continue;
            }

            try {
                $this->files->ensureDirectoryExists(dirname($to));
                $this->files->copyDirectory($from, $to);
            } catch (Throwable $exception) {
                $this->logError($exception);
            }
        }
    }

    public function runMigrationFiles(): void
    {
        SystemUpdateDBMigrating::dispatch();

        $migrator = app('migrator');

        rescue(fn() => $migrator->run(database_path('migrations')));

        $paths = [
            core_path(),
            package_path(),
        ];

        foreach ($paths as $path) {
            foreach (BaseHelper::scanFolder($path) as $module) {
                $modulePath = BaseHelper::joinPaths([$path, $module]);

                if (! $this->files->isDirectory($modulePath)) {
                    continue;
                }

                $moduleMigrationPath = BaseHelper::joinPaths([$modulePath, 'database', 'migrations']);

                if ($this->files->isDirectory($moduleMigrationPath)) {
                    $migrator->run($moduleMigrationPath);
                }
            }
        }

        SystemUpdateDBMigrated::dispatch();
    }

    private function validateUpdateFile(string $filePath): void
    {
        if (! class_exists('ZipArchive', false)) {
            throw new MissingZipExtensionException();
        }

        $zip = new ZipArchive();

        if ($zip->open($filePath)) {
            if ($zip->getFromName('.env')) {
                throw ValidationException::withMessages([
                    'file' => 'The update file contains a .env file. Please remove it and try again.',
                ]);
            }

            /**
             * @var array{
             *     productId: string,
             *     source: string,
             *     apiUrl: string,
             *     apiKey: string,
             *     version: string,
             *     minimumPhpVersion?: string,
             * }|null $content
             */
            $content = json_decode($zip->getFromName('platform/core/core.json'), true);

            if (! $content) {
                throw ValidationException::withMessages([
                    'file' => 'The update file is invalid. Please contact us for support.',
                ]);
            }

            $validator = Validator::make($content, [
                'productId'         => ['required', 'string'],
                'source'            => ['required', 'string'],
                'apiUrl'            => ['required', 'url'],
                'apiKey'            => ['required', 'string'],
                'version'           => ['required', 'string'],
                'marketplaceUrl'    => ['required', 'url'],
                'marketplaceToken'  => ['required', 'string'],
                'minimumPhpVersion' => ['nullable', 'string'],
            ])->stopOnFirstFailure();

            if ($validator->passes()) {
                if ($content['productId'] !== $this->productId) {
                    $zip->close();

                    throw ValidationException::withMessages(
                        ['productId' => 'The product ID of the update does not match the product ID of your website.']
                    );
                }

                if (version_compare($content['version'], $this->version, '<')) {
                    $zip->close();

                    throw ValidationException::withMessages(
                        ['version' => 'The version of the update is lower than the current version.']
                    );
                }

                if (
                    isset($content['minimumPhpVersion']) &&
                    version_compare($content['minimumPhpVersion'], phpversion(), '>')
                ) {
                    $zip->close();

                    throw ValidationException::withMessages(
                        [
                            'minimumPhpVersion' => sprintf(
                                'The minimum PHP version required (v%s) for the update is higher than the current PHP version.',
                                $content['minimumPhpVersion']
                            ),
                        ]
                    );
                }
            } else {
                $zip->close();

                throw ValidationException::withMessages($validator->errors()->toArray());
            }
        }

        $zip->close();
    }

    public function getCoreFileData(): array
    {
        if (self::$coreFileData) {
            return self::$coreFileData;
        }

        if ($this->cache->has('core_file_data') && $coreData = $this->cache->get('core_file_data')) {
            self::$coreFileData = $coreData;

            return $coreData;
        }

        return $this->getCoreFileDataFromDisk();
    }

    private function getCoreFileDataFromDisk(): array
    {
        try {
            $data = json_decode($this->files->get($this->coreDataFilePath), true) ?: [];

            self::$coreFileData = $data;

            $this->cache->put('core_file_data', $data, Carbon::now()->addMinutes(30));

            return $data;
        } catch (FileNotFoundException) {
            return [];
        }
    }

    private function getClientIpAddress(): string
    {
        $staticIp = config('core.base.general.static_ip');

        if ($staticIp && filter_var($staticIp, FILTER_VALIDATE_IP)) {
            return $staticIp;
        }

        return Helper::getIpFromThirdParty();
    }

    public function getServerIP(): string
    {
        return $this->getClientIpAddress();
    }

}
