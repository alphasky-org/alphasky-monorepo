<?php

namespace Alphasky\ACL\Repositories\Interfaces;

use Alphasky\Support\Repositories\Interfaces\RepositoryInterface;

interface UserInterface extends RepositoryInterface
{
    public function getUniqueUsernameFromEmail(string $email): string;
}
