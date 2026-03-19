<?php

namespace Alphasky\Base\GlobalSearch;

use Alphasky\Base\Contracts\GlobalSearchableManager as GlobalSearchableManagerContract;
use Alphasky\Base\Contracts\GlobalSearchableProvider as GlobalSearchableProviderContract;
use Alphasky\Base\GlobalSearch\Providers\DashboardMenuProvider;
use Alphasky\Base\GlobalSearch\Providers\PanelSectionProvider;
use Illuminate\Support\Collection;

class GlobalSearchableManager implements GlobalSearchableManagerContract
{
    public function __construct(
        protected array $providers = [
            DashboardMenuProvider::class,
            PanelSectionProvider::class,
        ],
        protected array $resolvedProviders = [],
    ) {
    }

    public function registerProvider(string $provider): static
    {
        $this->providers[] = $provider;

        return $this;
    }

    public function search(string $keyword, int $limit = 20): Collection
    {
        $result = collect();

        foreach ($this->providers as $provider) {
            $result = $result->merge(
                $this->getProvider($provider)->search($keyword)->take($limit)
            );
        }

        return $result;
    }

    protected function getProvider(string $provider): GlobalSearchableProviderContract
    {
        return $this->resolvedProviders[$provider] ??= app($provider);
    }
}
