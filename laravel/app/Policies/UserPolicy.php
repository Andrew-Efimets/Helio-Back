<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    protected function checkPrivacy(
        User $user,
        User $model,
        string $key,
        string $default = 'public'
    ): bool

    {
        if ($user->id === $model->id) return true;

        $privacy = $model->profile->privacy ?? [];
        $setting = $privacy[$key] ?? $default;

        if ($setting === 'public') return true;

        if ($setting === 'contacts_only') {
            if ($model->relationLoaded('contactPivot')) {
                return $model->contactPivot !== null && $model->contactPivot->status === 'accepted';
            }

            return $model->isContactWith($user);
        }

        return false;
    }

    public function viewParam(User $user, User $model, string $param): bool
    {
        $defaults = [
            'show_phone'    => 'public',
            'show_account'  => 'public',
            'show_photo'    => 'public',
            'show_video'    => 'public',
            'show_contacts' => 'public',
        ];

        return $this->checkPrivacy(
            $user,
            $model,
            $param,
            $defaults[$param] ?? 'public'
        );
    }
}
