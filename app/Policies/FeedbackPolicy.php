<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isAdministrative();
    }

    public function view(User $user, Feedback $feedback): bool
    {
        return $user->role->isAdministrative();
    }

    public function create(User $user): bool
    {
        // التقييمات تُنشأ من الموقع العام فقط (بلا مصادقة)
        return false;
    }

    public function update(User $user, Feedback $feedback): bool
    {
        return false;
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return $user->role->isAdministrative();
    }
}
