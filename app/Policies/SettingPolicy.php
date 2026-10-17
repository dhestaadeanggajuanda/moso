<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Policies\Concerns\GrantsByPermission;

class SettingPolicy
{
    use GrantsByPermission;

    protected function permission(): PermissionName
    {
        return PermissionName::SettingsManage;
    }
}
