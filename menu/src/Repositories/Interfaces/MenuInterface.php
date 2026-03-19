<?php

namespace Alphasky\Menu\Repositories\Interfaces;

use Alphasky\Base\Models\BaseModel;
use Alphasky\Support\Repositories\Interfaces\RepositoryInterface;

interface MenuInterface extends RepositoryInterface
{
    public function findBySlug(string $slug, bool $active, array $select = [], array $with = []): ?BaseModel;

    public function createSlug(string $name): string;
}
