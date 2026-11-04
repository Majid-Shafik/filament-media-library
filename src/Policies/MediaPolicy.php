<?php

declare(strict_types=1);

namespace MajidShafik\FilamentMediaLibrary\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $user, string $ability): ?bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return null;
    }

    public function viewAny(AuthUser $user): bool
    {
        return $this->checkPermission($user, ['ViewAny:Media', 'view_any_media', 'view_media']);
    }

    public function view(AuthUser $user, mixed $model = null): bool
    {
        return $this->checkPermission($user, ['View:Media', 'view_media']);
    }

    public function create(AuthUser $user): bool
    {
        return $this->checkPermission($user, ['Create:Media', 'create_media']);
    }

    public function update(AuthUser $user, mixed $model = null): bool
    {
        return $this->checkPermission($user, ['Update:Media', 'update_media']);
    }

    public function delete(AuthUser $user, mixed $model = null): bool
    {
        return $this->checkPermission($user, ['Delete:Media', 'delete_media']);
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $this->checkPermission($user, ['DeleteAny:Media', 'delete_any_media']);
    }

    protected function checkPermission(AuthUser $user, array $permissions): bool
    {
        if (!method_exists($user, 'can')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

