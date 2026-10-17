<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Approver = 'Approver';
    case Editor = 'Editor';
    case Kontributor = 'Kontributor';
}
