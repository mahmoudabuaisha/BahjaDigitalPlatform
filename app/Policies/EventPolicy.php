<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return $user->role->isAdministrative()
            || $event->team_id === $user->team_id;
    }

    public function create(User $user): bool
    {
        return $user->role->isAdministrative()
            || ($user->role === UserRole::TeamManager && $user->team_id !== null);
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->role->isAdministrative()) {
            return true;
        }

        // مسؤول الفريق يعدّل فعاليات فريقه فقط، ولا يعدّل الملغاة أو المنفَّذة
        return $event->team_id === $user->team_id
            && in_array($event->status, [
                EventStatus::Draft,
                EventStatus::Pending,
                EventStatus::Rejected,
                EventStatus::Approved,
            ], true);
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->role->isAdministrative()) {
            return true;
        }

        // الفريق يحذف مسوداته ومرفوضاته فقط — الملغاة تبقى للأرشيف
        return $event->team_id === $user->team_id
            && in_array($event->status, [EventStatus::Draft, EventStatus::Rejected], true);
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->role->isAdministrative();
    }

    public function forceDelete(User $user, Event $event): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
