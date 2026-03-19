<?php

namespace Alphasky\ACL\Repositories\Interfaces;

use Alphasky\Support\Repositories\Interfaces\RepositoryInterface;

interface RoleInterface extends RepositoryInterface
{
    public function createSlug(string $name, int|string $id): string;
}
