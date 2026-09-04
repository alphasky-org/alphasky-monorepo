<?php

return [
    'enable_plugin_manager' => env('CMS_PLUGIN_ENABLE_PLUGIN_MANAGER', true),
    'hide_plugin_author' => env('CMS_PLUGIN_HIDE_AUTHOR', false),
    'enable_plugin_list_cache' => env('CMS_PLUGIN_ENABLE_PLUGIN_LIST_CACHE', false),
    'enable_marketplace_feature' => env('CMS_ENABLE_MARKETPLACE_FEATURE', true),
    'auto_update_plugins_on_core_update' => env('CMS_PLUGIN_AUTO_UPDATE_ON_CORE_UPDATE', true),
    'alphasky_monorepo' => [
        'auto_update' => env('CMS_ALPHASKY_MONOREPO_AUTO_UPDATE', true),
        'repository' => env('CMS_ALPHASKY_MONOREPO_REPOSITORY', 'https://github.com/alphasky-org/alphasky-monorepo.git'),
        'branch' => env('CMS_ALPHASKY_MONOREPO_BRANCH', 'main'),
        'path' => env('CMS_ALPHASKY_MONOREPO_PATH', 'vendor/alphasky'),
        'version_url' => env('CMS_ALPHASKY_MONOREPO_VERSION_URL', 'https://raw.githubusercontent.com/alphasky-org/alphasky-monorepo/main/VERSION'),
        'clean_untracked' => env('CMS_ALPHASKY_MONOREPO_CLEAN_UNTRACKED', true),
        'update_timeout' => env('CMS_ALPHASKY_MONOREPO_UPDATE_TIMEOUT', 300),
    ],
];
