<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function view(User $user, Team $team): bool
    {
        return $user->role->isAdministrative()
            || $user->team_id === $team->id;
    }

    public function create(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function update(User $user, Team $team): bool
    {
        // مسؤول الفريق يحدّث ملف فريقه (عبر صفحة الملف التعريفي في لوحته)
        return $user->role->isAdministrative()
            || $user->team_id === $team->id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->role->isAdministrative();
    }

    public function restore(User $user, Team $team): bool
    {
        return $user->role->isAdministrative();
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
