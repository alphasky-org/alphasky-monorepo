<?php

namespace Alphasky\Widget\Repositories\Interfaces;

use Alphasky\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface WidgetInterface extends RepositoryInterface
{
    public function getByTheme(string $theme): Collection;
}
