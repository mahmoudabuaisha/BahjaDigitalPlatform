<?php

namespace App\Policies;

use App\Models\ShelterCenter;
use App\Models\User;

class ShelterCenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function view(User $user, ShelterCenter $shelterCenter): bool
    {
        return $user->role->isAdministrative();
    }

    public function create(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function update(User $user, ShelterCenter $shelterCenter): bool
    {
        return $user->role->isAdministrative();
    }

    public function delete(User $user, ShelterCenter $shelterCenter): bool
    {
        return $user->role->isAdministrative();
    }
}
