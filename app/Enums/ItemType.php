<?php

namespace App\Enums;

enum ItemType: string
{
    case LTO_SANITY_MEDS = 'l_t_o_sanity_meds';
    case MISSION_ITEM = 'mission_item';
    case BASIC_FACILITY = 'basic_facility';
    case PRECIPITATION_MANIFEST = 'precipitation_manifest';
    case PROFILE_THEME = 'profile_theme';
    case GEAR = 'gear';
    case OPERATOR_TOKEN = 'operator_token';
    case OPERATOR_SNAPSHOT = 'operator_snapshot';
    case FORMULA_UNLOCK = 'formula_unlock';
    case GIFT = 'gift';
    case ESSENCE = 'essence';
    case REGIONAL_BUFF = 'regional_buff';
    case WEAPON = 'weapon';
    case GEAR_TEMPLATE = 'gear_template';
    case EMPLOYMENT_CONTRACT = 'employment_contract';
    case PHOTOGRAPH_STICKER = 'photograph_sticker';
    case MATERIAL = 'material';
    case CONSUMABLE = 'consumable';
    case RARE_PROGRESSION_MATERIAL = 'rare_progression_material';
    case RESEARCH_CHIP = 'research_chip';
    case TACTICAL = 'tactical';
    case LOGISTICS = 'logistics';
    case PUZZLE_ITEM = 'puzzle_item';
    case PORTRAIT = 'portrait';
    case SYSTEM_BLUEPRINT = 'system_blueprint';
    case STANDARD_TEMPLATE_ASSEMBLY = 'standard_template_assembly';
    case PRODUCT_UPGRADE = 'product_upgrade';
    case FLUID_STORAGE = 'fluid_storage';
    case ENGRAVING_PERMIT = 'engraving_permit';
    case ESSENCE_ENGRAVING_KIT = 'essence_engraving_kit';
    case VALUABLE = 'valuable';
    case GEAR_ARTIFICING_SALVE = 'gear_artificing_salve';
    case PASS_APPLICATION = 'pass_application';
    case MISCELLANEOUS = 'miscellaneous';
    case PRECIPITATION_NODULE = 'precipitation_nodule';
    case ELASTIC_GOODS = 'elastic_goods';
    case OPERATIONAL_EXP = 'operational_e_x_p';
    case SANITY = 'sanity';
    case SANITY_ITEM = 'sanity_item';
    case SANITY_MEDS = 'sanity_meds';
    case STOCK_BILL = 'stock_bill';
    case CRATE = 'crate';
    case OUTPOST_PROTOCOL_CAPACITY = 'outpost_protocol_capacity';
    case OPERATOR_MATERIAL = 'operator_material';
    case PASS_EXPERIENCE = 'pass_experience';
    case SEED = 'seed';
    case DAILY_ACTIVITY = 'daily_activity';
    case CURRENCY = 'currency';
    case REGIONAL_DEVELOPMENT_METRIC = 'regional_development_metric';
    case WEAPON_MATERIAL = 'weapon_material';
    case CRYSTAL = 'crystal';
    case ESSENCE_MATERIAL = 'essence_material';
    case LTO_VALUABLE = 'l_t_o_valuable';
    case SPECIAL_FACILITY = 'special_facility';
    case MONTHLY_PASS_EXCHANGE_CERT = 'monthly_pass_exchange_cert';
    case PROXC = 'p_r_o_x_c';
    case MINING_SPOT = 'mining_spot';
    case AIC_TECH_INDEX = 'aic_tech_index';
    case CAMERA_FILTER = 'camera_filter';
    case HEADHUNTING_DOSSIER = 'headhunting_dossier';
    case DIJIANG_DISPLAY_ITEMS = 'dijiang_display_items';
    case MUSIC = 'music';
    case DETECTOR = 'detector';
    case OPERATION_PROGRESS = 'operation_progress';
    case OUTPOST_PROSPERITY = 'outpost_prosperity';
    case PORTRAIT_FRAME = 'portrait_frame';
    case AURYLENE_CRYSTAL = 'aurylene_crystal';

    public function displayName(): string
    {
        return match ($this) {
            self::LTO_SANITY_MEDS => 'LTO Sanity Meds',
            self::MISSION_ITEM => 'Mission Item',
            self::BASIC_FACILITY => 'Basic Facility',
            self::PRECIPITATION_MANIFEST => 'Precipitation Manifest',
            self::PROFILE_THEME => 'Profile Theme',
            self::GEAR => 'Gear',
            self::OPERATOR_TOKEN => 'Operator Token',
            self::OPERATOR_SNAPSHOT => 'Operator Snapshot',
            self::FORMULA_UNLOCK => 'Formula Unlock',
            self::GIFT => 'Gift',
            self::ESSENCE => 'Essence',
            self::REGIONAL_BUFF => 'Regional Buff',
            self::WEAPON => 'Weapon',
            self::GEAR_TEMPLATE => 'Gear Template',
            self::EMPLOYMENT_CONTRACT => 'Employment Contract',
            self::PHOTOGRAPH_STICKER => 'Photograph Sticker',
            self::MATERIAL => 'Material',
            self::CONSUMABLE => 'Consumable',
            self::RARE_PROGRESSION_MATERIAL => 'Rare Progression Material',
            self::RESEARCH_CHIP => 'Research Chip',
            self::TACTICAL => 'Tactical',
            self::LOGISTICS => 'Logistics',
            self::PUZZLE_ITEM => 'Puzzle Item',
            self::PORTRAIT => 'Portrait',
            self::SYSTEM_BLUEPRINT => 'System Blueprint',
            self::STANDARD_TEMPLATE_ASSEMBLY => 'Standard Template Assembly',
            self::PRODUCT_UPGRADE => 'Product Upgrade',
            self::FLUID_STORAGE => 'Fluid Storage',
            self::ENGRAVING_PERMIT => 'Engraving Permit',
            self::ESSENCE_ENGRAVING_KIT => 'Essence Engraving Kit',
            self::VALUABLE => 'Valuable',
            self::GEAR_ARTIFICING_SALVE => 'Gear Artificing Salve',
            self::PASS_APPLICATION => 'Pass Application',
            self::MISCELLANEOUS => 'Miscellaneous',
            self::PRECIPITATION_NODULE => 'Precipitation Nodule',
            self::ELASTIC_GOODS => 'Elastic Goods',
            self::OPERATIONAL_EXP => 'Operational EXP',
            self::SANITY => 'Sanity',
            self::SANITY_ITEM => 'Sanity Item',
            self::SANITY_MEDS => 'Sanity Meds',
            self::STOCK_BILL => 'Stock Bill',
            self::CRATE => 'Crate',
            self::OUTPOST_PROTOCOL_CAPACITY => 'Outpost Protocol Capacity',
            self::OPERATOR_MATERIAL => 'Operator Material',
            self::PASS_EXPERIENCE => 'Pass Experience',
            self::SEED => 'Seed',
            self::DAILY_ACTIVITY => 'Daily Activity',
            self::CURRENCY => 'Currency',
            self::REGIONAL_DEVELOPMENT_METRIC => 'Regional Development Metric',
            self::WEAPON_MATERIAL => 'Weapon Material',
            self::CRYSTAL => 'Crystal',
            self::ESSENCE_MATERIAL => 'Essence Material',
            self::LTO_VALUABLE => 'LTO Valuable',
            self::SPECIAL_FACILITY => 'Special Facility',
            self::MONTHLY_PASS_EXCHANGE_CERT => 'Monthly Pass Exchange Cert',
            self::PROXC => 'PROXC',
            self::MINING_SPOT => 'Mining Spot',
            self::AIC_TECH_INDEX => 'AIC Tech Index',
            self::CAMERA_FILTER => 'Camera Filter',
            self::HEADHUNTING_DOSSIER => 'Headhunting Dossier',
            self::DIJIANG_DISPLAY_ITEMS => 'Dijiang: Display Items',
            self::MUSIC => 'Music',
            self::DETECTOR => 'Detector',
            self::OPERATION_PROGRESS => 'Operation Progress',
            self::OUTPOST_PROSPERITY => 'Outpost Prosperity',
            self::PORTRAIT_FRAME => 'Portrait Frame',
            self::AURYLENE_CRYSTAL => 'Aurylene Crystal',
            default => 'Unknown',
        };
    }

    /**
     * Returns an array of types that are considered craftable.
     *
     * @return array<ItemType>
     */
    public static function craftableTypes(): array
    {
        // Update this array as domain knowledge of craftable types is confirmed
        return [
            self::MATERIAL,
            self::CONSUMABLE,
            self::RARE_PROGRESSION_MATERIAL,
            self::CRYSTAL,
            self::WEAPON_MATERIAL,
            self::GEAR,
            self::ESSENCE_MATERIAL,
            self::TACTICAL,
        ];
    }

    public static function craftableTypeIds(): array
    {
        return [8, 52, 95, 45, 25, 6, 61, 82, 48];
    }
}
