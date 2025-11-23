<?php

namespace Database\Seeders;

use App\Enums\Locale;
use App\Enums\TagType;
use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blueprint_tags = [
            TagType::BLUEPRINT_TAGS->value => [
                [
                    Locale::ENGLISH->value => 'Production',
                    Locale::JAPANESE->value => '生産',
                    Locale::KOREAN->value => '생산',
                    Locale::TRADITIONAL_CHINESE->value => '生產',
                    Locale::SIMPLIFIED_CHINESE->value => '生产',
                    Locale::SPANISH->value => 'Producción',
                    Locale::PORTUGESE->value => 'Produção',
                    Locale::FRENCH->value => 'Production',
                    Locale::GERMAN->value => 'Produktion',
                    Locale::RUSSIAN->value => 'Производство',
                    Locale::ITALIAN->value => 'Produzione',
                    Locale::INDONESIAN->value => 'Produksi',
                    Locale::THAI->value => 'การผลิต',
                    Locale::VIET->value => 'Sản xuất',
                ],
                [
                    Locale::ENGLISH->value => 'Exploration',
                    Locale::JAPANESE->value => '探索',
                    Locale::KOREAN->value => '탐험',
                    Locale::TRADITIONAL_CHINESE->value => '探索',
                    Locale::SIMPLIFIED_CHINESE->value => '研究',
                    Locale::SPANISH->value => 'Exploración',
                    Locale::PORTUGESE->value => 'Exploração',
                    Locale::FRENCH->value => 'Exploration',
                    Locale::GERMAN->value => 'Erkundung',
                    Locale::RUSSIAN->value => 'Исследование',
                    Locale::ITALIAN->value => 'Esplorazione',
                    Locale::INDONESIAN->value => 'Eksplorasi',
                    Locale::THAI->value => 'การสำรวจ',
                    Locale::VIET->value => 'Khám phá',
                ],
                [
                    Locale::ENGLISH->value => 'Combat',
                    Locale::JAPANESE->value => '戦闘',
                    Locale::KOREAN->value => '전투',
                    Locale::TRADITIONAL_CHINESE->value => '戰鬥',
                    Locale::SIMPLIFIED_CHINESE->value => '存储',
                    Locale::SPANISH->value => 'Combate',
                    Locale::PORTUGESE->value => 'Combate',
                    Locale::FRENCH->value => 'Combat',
                    Locale::GERMAN->value => 'Kampf',
                    Locale::RUSSIAN->value => 'Бой',
                    Locale::ITALIAN->value => 'Combattimento',
                    Locale::INDONESIAN->value => 'Pertempuran',
                    Locale::THAI->value => 'การต่อสู้',
                    Locale::VIET->value => 'Chiến đấu',
                ],
                [
                    Locale::ENGLISH->value => 'Agriculture',
                    Locale::JAPANESE->value => '農業',
                    Locale::KOREAN->value => '농업',
                    Locale::TRADITIONAL_CHINESE->value => '農業',
                    Locale::SIMPLIFIED_CHINESE->value => '农业',
                    Locale::SPANISH->value => 'Agricultura',
                    Locale::PORTUGESE->value => 'Agricultura',
                    Locale::FRENCH->value => 'Agriculture',
                    Locale::GERMAN->value => 'Landwirtschaft',
                    Locale::RUSSIAN->value => 'Сельское хозяйство',
                    Locale::ITALIAN->value => 'Agricoltura',
                    Locale::INDONESIAN->value => 'Pertanian',
                    Locale::THAI->value => 'เกษตรกรรม',
                    Locale::VIET->value => 'Nông nghiệp',
                ],
                [
                    Locale::ENGLISH->value => 'Logistics',
                    Locale::JAPANESE->value => '物流',
                    Locale::KOREAN->value => '물류',
                    Locale::TRADITIONAL_CHINESE->value => '物流',
                    Locale::SIMPLIFIED_CHINESE->value => '物流',
                    Locale::SPANISH->value => 'Logística',
                    Locale::PORTUGESE->value => 'Logística',
                    Locale::FRENCH->value => 'Logistique',
                    Locale::GERMAN->value => 'Logistik',
                    Locale::RUSSIAN->value => 'Логистика',
                    Locale::ITALIAN->value => 'Logistica',
                    Locale::INDONESIAN->value => 'Logistik',
                    Locale::THAI->value => 'โลจิสติกส์',
                    Locale::VIET->value => 'Hậu cần',
                ],
            ],
            TagType::BLUEPRINT_TIER->value => [
                [
                    Locale::ENGLISH->value => 'I',
                    Locale::JAPANESE->value => 'I',
                    Locale::KOREAN->value => 'I',
                    Locale::TRADITIONAL_CHINESE->value => 'I',
                    Locale::SIMPLIFIED_CHINESE->value => 'I',
                    Locale::SPANISH->value => 'I',
                    Locale::PORTUGESE->value => 'I',
                    Locale::FRENCH->value => 'I',
                    Locale::GERMAN->value => 'I',
                    Locale::RUSSIAN->value => 'I',
                    Locale::ITALIAN->value => 'I',
                    Locale::INDONESIAN->value => 'I',
                    Locale::THAI->value => 'I',
                    Locale::VIET->value => 'I',
                ],
                [
                    Locale::ENGLISH->value => 'II',
                    Locale::JAPANESE->value => 'II',
                    Locale::KOREAN->value => 'II',
                    Locale::TRADITIONAL_CHINESE->value => 'II',
                    Locale::SIMPLIFIED_CHINESE->value => 'II',
                    Locale::SPANISH->value => 'II',
                    Locale::PORTUGESE->value => 'II',
                    Locale::FRENCH->value => 'II',
                    Locale::GERMAN->value => 'II',
                    Locale::RUSSIAN->value => 'II',
                    Locale::ITALIAN->value => 'II',
                    Locale::INDONESIAN->value => 'II',
                    Locale::THAI->value => 'II',
                    Locale::VIET->value => 'II',
                ],
                [
                    Locale::ENGLISH->value => 'III',
                    Locale::JAPANESE->value => 'III',
                    Locale::KOREAN->value => 'III',
                    Locale::TRADITIONAL_CHINESE->value => 'III',
                    Locale::SIMPLIFIED_CHINESE->value => 'III',
                    Locale::SPANISH->value => 'III',
                    Locale::PORTUGESE->value => 'III',
                    Locale::FRENCH->value => 'III',
                    Locale::GERMAN->value => 'III',
                    Locale::RUSSIAN->value => 'III',
                    Locale::ITALIAN->value => 'III',
                    Locale::INDONESIAN->value => 'III',
                    Locale::THAI->value => 'III',
                    Locale::VIET->value => 'III',
                ],
                [
                    Locale::ENGLISH->value => 'IV',
                    Locale::JAPANESE->value => 'IV',
                    Locale::KOREAN->value => 'IV',
                    Locale::TRADITIONAL_CHINESE->value => 'IV',
                    Locale::SIMPLIFIED_CHINESE->value => 'IV',
                    Locale::SPANISH->value => 'IV',
                    Locale::PORTUGESE->value => 'IV',
                    Locale::FRENCH->value => 'IV',
                    Locale::GERMAN->value => 'IV',
                    Locale::RUSSIAN->value => 'IV',
                    Locale::ITALIAN->value => 'IV',
                    Locale::INDONESIAN->value => 'IV',
                    Locale::THAI->value => 'IV',
                    Locale::VIET->value => 'IV',
                ],
            ],
            TagType::BLUEPRINT_TYPE->value => [
                [
                    Locale::ENGLISH->value => 'PAC',
                    Locale::JAPANESE->value => 'PAC',
                    Locale::KOREAN->value => 'PAC',
                    Locale::TRADITIONAL_CHINESE->value => 'PAC',
                    Locale::SIMPLIFIED_CHINESE->value => '生产',
                    Locale::SPANISH->value => 'PAC',
                    Locale::PORTUGESE->value => 'PAC',
                    Locale::FRENCH->value => 'PAC',
                    Locale::GERMAN->value => 'PAC',
                    Locale::RUSSIAN->value => 'PAC',
                    Locale::ITALIAN->value => 'PAC',
                    Locale::INDONESIAN->value => 'PAC',
                    Locale::THAI->value => 'PAC',
                    Locale::VIET->value => 'PAC',
                ],
                [
                    Locale::ENGLISH->value => 'Sub-PAC',
                    Locale::JAPANESE->value => 'Sub-PAC',
                    Locale::KOREAN->value => 'Sub-PAC',
                    Locale::TRADITIONAL_CHINESE->value => 'Sub-PAC',
                    Locale::SIMPLIFIED_CHINESE->value => '研究',
                    Locale::SPANISH->value => 'Sub-PAC',
                    Locale::PORTUGESE->value => 'Sub-PAC',
                    Locale::FRENCH->value => 'Sub-PAC',
                    Locale::GERMAN->value => 'Sub-PAC',
                    Locale::RUSSIAN->value => 'Sub-PAC',
                    Locale::ITALIAN->value => 'Sub-PAC',
                    Locale::INDONESIAN->value => 'Sub-PAC',
                    Locale::THAI->value => 'Sub-PAC',
                    Locale::VIET->value => 'Sub-PAC',
                ],
            ],
        ];

        foreach ($blueprint_tags as $type => $tags) {
            foreach ($tags as $tag) {
                $englishName = $tag[Locale::ENGLISH->value];
                Tag::create([
                    'name' => $tag,
                    'slug' => str($englishName)->slug(),
                    'type' => $type,
                ]);
            }
        }
    }
}
