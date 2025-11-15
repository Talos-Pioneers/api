<?php

namespace App\Enums;

enum ItemType: string
{
    case MATERIAL = 'material';
    case CURRENCY = 'currency';
    case OPERATOR_MATERIAL = 'operator_material';
    case MISSION_ITEMS = 'mission_items';
    case ESSENCE = 'essence';
    case RARE = 'rare';
    case SANITY_MEDS = 'sanity_meds';
    case GIFTS = 'gifts';
    case SEEDS = 'seeds';
    case OPERATIONAL_EXP = 'operational_exp';
    case WEAPON_MATERIAL = 'weapon_material';
    case SANITY = 'sanity';
    case CRATE = 'crate';
    case PROXC = 'proxc';
    case TATICAL = 'tactical';
    case OUTPOST_PROSPERITY = 'outpost_prosperity';
    case CONSUMABLES = 'consumables';
    case CRYSTAL = 'crystal';

    public function displayName(): string
    {
        return match ($this) {
            self::MATERIAL => 'Material',
            self::CURRENCY => 'Currency',
            self::OPERATOR_MATERIAL => 'Operator Material',
            self::MISSION_ITEMS => 'Mission Items',
            self::ESSENCE => 'Essence',
            self::RARE => 'Rare',
            self::SANITY_MEDS => 'Sanity Meds',
            self::GIFTS => 'Gifts',
            self::SEEDS => 'Seeds',
            self::OPERATIONAL_EXP => 'Operational Exp',
            self::WEAPON_MATERIAL => 'Weapon Material',
            self::SANITY => 'Sanity',
            self::CRATE => 'Crate',
            self::PROXC => 'Proxc',
            self::TATICAL => 'Tactical',
            self::OUTPOST_PROSPERITY => 'Outpost Prosperity',
            self::CONSUMABLES => 'Consumables',
            self::CRYSTAL => 'Crystal',
            default => 'Unknown',
        };
    }
}
