<?php

namespace Alphasky\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:install', 'Install CMS.')]
class InstallCommand extends Command
{
    public function handle(): int
    {
        if (! confirm('Do you want to proceed with installation?')) {
            return self::SUCCESS;
        }

        $this->components->info('Starting installation...');

        $environment = $this->promptForEnvironment();
        $this->writeEnvironmentFile($environment);
        $this->applyEnvironment($environment);
        $this->components->info('Environment file configured!');

        $this->components->info('Running migrate...');
        if ($this->call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
            $this->components->error('Migration failed. Installation stopped.');

            return self::FAILURE;
        }

        $this->components->info('Migrate done!');

        //if (confirm('Create a new super user?')) {
            $this->call('cms:user:create');
        //}

        //if (confirm('Do you want to activate all plugins?')) {
            $this->components->info('Activating all plugins...');
            $this->call('cms:plugin:activate:all');
            $this->components->info('All plugins are activated!');
        //}



        $this->components->info('Publishing assets...');
        $this->call('cms:publish:assets');
        $this->components->info('Publishing assets done!');

        $this->components->info('Your CMS is ready to use!');

        return self::SUCCESS;
    }

    private function promptForEnvironment(): array
    {
        $connection = 'mysql';

        return [
            'APP_NAME' => text('Site title', default: (string) config('app.name', 'Alphasky CMS'), required: true),
            'APP_URL' => text('Application URL', default: (string) config('app.url', 'http://127.0.0.1:8000'), required: true),
            'DB_CONNECTION' => $connection,
            'DB_HOST' => text('Database host', default: (string) config("database.connections.$connection.host", '127.0.0.1'), required: true),
            'DB_PORT' => text('Database port', default: (string) config("database.connections.$connection.port", '3306'), required: true),
            'DB_DATABASE' => text('Database name', default: (string) config("database.connections.$connection.database", ''), required: true),
            'DB_USERNAME' => text('Database username', default: (string) config("database.connections.$connection.username", 'root'), required: true),
            'DB_PASSWORD' => password('Database password (leave blank if none)'),
        ];
    }

    private function writeEnvironmentFile(array $environment): void
    {
        $environmentPath = base_path('.env');

        if (is_file($environmentPath)) {
            $content = file_get_contents($environmentPath);
        } else {
            $examplePath = base_path('.env.example');

            if (! is_file($examplePath)) {
                throw new RuntimeException('The .env.example file does not exist.');
            }

            $content = file_get_contents($examplePath);
        }

        if ($content === false) {
            throw new RuntimeException('Unable to read the environment file.');
        }

        foreach ($environment as $key => $value) {
            $line = $key . '=' . $this->quoteEnvironmentValue((string) $value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace_callback($pattern, fn () => $line, $content);
            } else {
                $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
            }
        }

        if (file_put_contents($environmentPath, $content) === false) {
            throw new RuntimeException('Unable to write the .env file.');
        }
    }

    private function applyEnvironment(array $environment): void
    {
        $connection = $environment['DB_CONNECTION'];
        $connectionName = "database.connections.$connection";

        config([
            'app.name' => $environment['APP_NAME'],
            'app.url' => $environment['APP_URL'],
            'database.default' => $connection,
            $connectionName => array_merge(config($connectionName, []), [
                'host' => $environment['DB_HOST'],
                'port' => $environment['DB_PORT'],
                'database' => $environment['DB_DATABASE'],
                'username' => $environment['DB_USERNAME'],
                'password' => $environment['DB_PASSWORD'],
            ]),
        ]);

        DB::purge($connection);
    }

    private function quoteEnvironmentValue(string $value): string
    {
        return '"' . addcslashes($value, '\\"') . '"';
    }
}
