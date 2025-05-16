<?php

namespace Marvel\Traits;

use Marvel\Enums\Permission;
use Marvel\Database\Models\User;

trait UsersTrait
{
    public function getAdminUsers()
    {
        return User::with('profile')
            ->where('is_active', true)
            ->permission(Permission::SUPER_ADMIN)
            ->get();
    }
}
