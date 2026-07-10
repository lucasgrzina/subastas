<?php

namespace App\Enums;

enum ExportType: string
{
    case USERS       = 'users';
    case ROLES       = 'roles';
    case API_CLIENTS = 'api-clients';

    public function label(): string
    {
        return match ($this) {
            self::USERS       => 'Usuarios',
            self::ROLES       => 'Roles',
            self::API_CLIENTS => 'Clientes API',
        };
    }
}
