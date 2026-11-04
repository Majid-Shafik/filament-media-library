<?php

declare(strict_types=1);

namespace MajidShafik\FilamentMediaLibrary\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaCategoryPolicy
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
        return $this->checkPermission($user, ['ViewAny:MediaCategory', 'view_any_media::category', 'view_any_media_category']);
    }

    public function view(AuthUser $user, mixed $model = null): bool
    {
        return $this->checkPermission($user, ['View:MediaCategory', 'view_media::category', 'view_media_category']);
    }

    public function create(AuthUser $user): bool
    {
        return $this->checkPermission($user, ['Create:MediaCategory', 'create_media::category', 'create_media_category']);
    }

    public function update(AuthUser $user, mixed $model = null): bool
    {
        return $this->checkPermission($user, ['Update:MediaCategory', 'update_media::category', 'update_media_category']);
    }

    public function delete(AuthUser $user, mixed $model = null): bool
    {
        return $this->checkPermission($user, ['Delete:MediaCategory', 'delete_media::category', 'delete_media_category']);
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $this->checkPermission($user, ['DeleteAny:MediaCategory', 'delete_any_media::category', 'delete_any_media_category']);
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

