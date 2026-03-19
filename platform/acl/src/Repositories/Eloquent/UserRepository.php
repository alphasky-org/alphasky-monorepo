<?php

namespace Alphasky\ACL\Repositories\Eloquent;

use Alphasky\ACL\Repositories\Interfaces\UserInterface;
use Alphasky\Support\Repositories\Eloquent\RepositoriesAbstract;

class UserRepository extends RepositoriesAbstract implements UserInterface
{
    public function getUniqueUsernameFromEmail(string $email): string
    {
        $emailPrefix = substr($email, 0, strpos($email, '@'));
        $username = $emailPrefix;
        $offset = 1;
        while ($this->getFirstBy(['username' => $username])) {
            $username = $emailPrefix . $offset;
            $offset++;
        }

        $this->resetModel();

        return $username;
    }
}
