<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tujuan;
use Illuminate\Auth\Access\HandlesAuthorization;

class TujuanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tujuan');
    }

    public function view(AuthUser $authUser, Tujuan $tujuan): bool
    {
        return $authUser->can('view_tujuan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tujuan');
    }

    public function update(AuthUser $authUser, Tujuan $tujuan): bool
    {
        return $authUser->can('update_tujuan');
    }

    public function delete(AuthUser $authUser, Tujuan $tujuan): bool
    {
        return $authUser->can('delete_tujuan');
    }

    public function restore(AuthUser $authUser, Tujuan $tujuan): bool
    {
        return $authUser->can('restore_tujuan');
    }

    public function forceDelete(AuthUser $authUser, Tujuan $tujuan): bool
    {
        return $authUser->can('force_delete_tujuan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tujuan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tujuan');
    }

    public function replicate(AuthUser $authUser, Tujuan $tujuan): bool
    {
        return $authUser->can('replicate_tujuan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tujuan');
    }

}