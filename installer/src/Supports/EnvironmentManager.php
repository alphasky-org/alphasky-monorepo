<?php

namespace Alphasky\Installer\Supports;

use Illuminate\Http\Request;
use Throwable;

class EnvironmentManager
{
    public function save(Request $request): string
    {
        $results = trans('packages/installer::installer.environment.success');

        $content = file_get_contents(base_path('.env.example'));

        $replacements = [
            'APP_NAME' => [
                'value' => '"' . str_replace('"', '', $request->input('app_name')) . '"',
            ],
            'APP_URL' => [
                'value' => $request->input('app_url'),
            ],
            'FORCE_ROOT_URL' => [
                'value' => $request->input('app_url'),
            ],
            'DB_CONNECTION' => [
                'value' => $request->input('database_connection'),
            ],
            'DB_HOST' => [
                'value' => $request->input('database_hostname'),
            ],
            'DB_PORT' => [
                'value' => $request->input('database_port'),
            ],
            'DB_DATABASE' => [
                'value' => '"' . str_replace('"', '', $request->input('database_name')) . '"',
            ],
            'DB_USERNAME' => [
                'value' => '"' . str_replace('"', '', $request->input('database_username')) . '"',
            ],
            'DB_PASSWORD' => [
                'value' => '"' . str_replace('"', '', $request->input('database_password')) . '"',
            ],
        ];

        foreach ($replacements as $key => $replacement) {
            $content = preg_replace_callback(
                '/^' . preg_quote($key, '/') . '=.*$/m',
                fn () => $key . '=' . $replacement['value'],
                $content
            );
        }

        try {
            file_put_contents(base_path('.env'), $content);
        } catch (Throwable) {
            $results = trans('packages/installer::installer.environment.errors');
        }

        return $results;
    }

    public function turnOffDebugMode(): void
    {
        $content = file_get_contents(base_path('.env'));

        $content = preg_replace('/^APP_DEBUG=true/m', 'APP_DEBUG=false', $content);

        try {
            file_put_contents(base_path('.env'), $content);
        } catch (Throwable) {
        }
    }
}
