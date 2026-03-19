<?php

namespace Alphasky\ACL\Events;

use Alphasky\ACL\Models\Role;
use Alphasky\ACL\Models\User;
use Alphasky\Base\Events\Event;
use Illuminate\Queue\SerializesModels;

class RoleAssignmentEvent extends Event
{
    use SerializesModels;

    public function __construct(public Role $role, public User $user)
    {
    }
}
