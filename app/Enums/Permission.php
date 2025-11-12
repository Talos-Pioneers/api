<?php

namespace App\Enums;

enum Permission: string
{
    case MANAGE_TAGS = 'manage_tags';
    case UPGRADE_USERS = 'upgrade_users';
    case MANAGE_ALL_BLUEPRINTS = 'manage_all_blueprints';
    case MANAGE_ALL_COLLECTIONS = 'manage_all_collections';
}
