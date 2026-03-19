<?php

namespace Alphasky\ACL\Repositories\Interfaces;

use Alphasky\ACL\Models\User;
use Alphasky\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

interface ActivationInterface
{
    public function createUser(User $user): BaseModel|Model;

    public function exists(User $user, ?string $code = null): BaseModel|bool;

    public function complete(User $user, string $code): bool;

    public function completed(User $user): BaseModel|bool;

    public function remove(User $user);

    public function removeExpired();
}
