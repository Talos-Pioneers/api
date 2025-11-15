<?php

namespace App\Enums;

enum FacilityType: string
{
    case ASSEMBLE_MACHINE = 'assemble_machine';
    case BASIC_MACHINE = 'basic_machine';
    case BATTLE_MACHINE = 'battle_machine';
    case CUSTOM = 'custom';
    case ELECTRIC_MACHINE = 'electric_machine';
    case EXTRA_MACHINE = 'extra_machine';
    case LOGISTIC = 'logistic';
    case SOURCE_MACHINE = 'source_machine';

    /**
     * Get the display name for the facility type.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ASSEMBLE_MACHINE => 'Gear',
            self::BASIC_MACHINE => 'Processing',
            self::BATTLE_MACHINE => 'Combat',
            self::CUSTOM => 'Quick Construct',
            self::ELECTRIC_MACHINE => 'Power',
            self::EXTRA_MACHINE => 'Miscellaneous',
            self::LOGISTIC => 'Logistics',
            self::SOURCE_MACHINE => 'Mining',
        };
    }

    /**
     * Get the display name for a type string (like the original JS function).
     */
    public static function displayNameFor(?string $type): string
    {
        $enum = self::tryFrom($type);

        return $enum?->displayName() ?? 'Unknown';
    }
}
