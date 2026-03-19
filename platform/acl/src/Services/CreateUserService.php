<?php

namespace Alphasky\ACL\Services;

use Alphasky\ACL\Events\RoleAssignmentEvent;
use Alphasky\ACL\Models\Role;
use Alphasky\ACL\Models\User;
use Alphasky\Support\Services\ProduceServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CreateUserService implements ProduceServiceInterface
{
    public function __construct(protected ActivateUserService $activateUserService)
    {
    }

    public function execute(Request $request): User
    {
        $user = new User();
        $user->fill($request->input());
        $user->password = Hash::make($request->input('password'));
        $user->save();

        if (
            $this->activateUserService->activate($user) &&
            ($roleId = $request->input('role_id')) &&
            $role = Role::query()->find($roleId)
        ) {
            /**
             * @var Role $role
             */
            $role->users()->attach($user->getKey());

            event(new RoleAssignmentEvent($role, $user));
        }

        return $user;
    }
}
