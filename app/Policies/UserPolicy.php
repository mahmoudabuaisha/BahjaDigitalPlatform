<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function view(User $user, User $model): bool
    {
        return $user->role->isAdministrative();
    }

    public function create(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->role->isAdministrative()) {
            return false;
        }

        // حسابات المشرفين والمدير العام حكر على المدير العام
        if ($model->role->isAdministrative()) {
            return $user->role === UserRole::SuperAdmin;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        // لا أحد يحذف حسابه، وحسابات الإدارة حكر على المدير العام
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->role->isAdministrative()) {
            return $user->role === UserRole::SuperAdmin;
        }

        return $user->role->isAdministrative();
    }
}
