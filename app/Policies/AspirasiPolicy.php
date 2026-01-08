<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Aspirasi;
use Illuminate\Auth\Access\HandlesAuthorization;

class AspirasiPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_aspirasi');
    }

    public function view(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        return $authUser->can('view_aspirasi');
    }

    public function viewAdminSummary(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_aspirasi');
    }

    public function viewIdentity(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        if (! $aspirasi->is_anonymous) {
            return true;
        }

        return $authUser->can('view_identity_aspirasi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_aspirasi');
    }

    public function update(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        return $authUser->can('update_aspirasi');
    }

    public function delete(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        return $authUser->can('delete_aspirasi');
    }

    public function restore(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        return $authUser->can('restore_aspirasi');
    }

    public function forceDelete(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        return $authUser->can('force_delete_aspirasi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_aspirasi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_aspirasi');
    }

    public function replicate(AuthUser $authUser, Aspirasi $aspirasi): bool
    {
        return $authUser->can('replicate_aspirasi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_aspirasi');
    }

}
