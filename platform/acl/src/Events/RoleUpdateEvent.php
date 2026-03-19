<?php

namespace Alphasky\ACL\Events;

use Alphasky\ACL\Models\Role;
use Alphasky\Base\Events\Event;
use Illuminate\Queue\SerializesModels;

class RoleUpdateEvent extends Event
{
    use SerializesModels;

    public function __construct(public Role $role)
    {
    }
}
