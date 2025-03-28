<?php


namespace Marvel\Enums;

use BenSampo\Enum\Enum;

/**
 * Class RoleType
 * @package App\Enums
 */
final class Permission extends Enum
{
    public const SUPER_ADMIN = 'super_admin';
    public const STORE_OWNER = 'company';
    public const STAFF = 'staff';
    public const CUSTOMER = 'employee';
    public const ADMIN_STAFF = 'admin_staff';
}
