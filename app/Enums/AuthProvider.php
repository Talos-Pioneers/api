<?php

namespace App\Enums;

enum AuthProvider: string
{
    case DISCORD = 'discord';
    case GOOGLE = 'google';
}
