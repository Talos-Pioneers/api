<?php

namespace App\Enums;

enum Locale: string
{
    case ENGLISH = 'en-US';
    case JAPANESE = 'ja-JP';
    case KOREAN = 'ko-KR';
    case TRADITIONAL_CHINESE = 'zh-TW';
    case SIMPLIFIED_CHINESE = 'zh-CN';
    case SPANISH = 'es-MX';
    case PORTUGESE = 'pt-BR';
    case FRENCH = 'fr-FR';
    case GERMAN = 'de-DE';
    case RUSSIAN = 'ru-RU';
    case ITALIAN = 'it-IT';
    case INDONESIAN = 'id-ID';
    case THAI = 'th-TH';
    case VIET = 'vt-VN';

    public static function fromString(string $locale): ?Locale
    {
        return match ($locale) {
            'en', 'en-US', 'en-GB' => Locale::ENGLISH,
            'ja', 'ja-JP', 'jp' => Locale::JAPANESE,
            'ko', 'ko-KR', 'kr' => Locale::KOREAN,
            'tw', 'zh-TW', 'tc' => Locale::TRADITIONAL_CHINESE,
            'zh', 'cn', 'zh-CN' => Locale::SIMPLIFIED_CHINESE,
            'es', 'es-MX', 'es-ES' => Locale::SPANISH,
            'pt', 'pt-BR' => Locale::PORTUGESE,
            'fr', 'fr-FR' => Locale::FRENCH,
            'de', 'de-DE' => Locale::GERMAN,
            'ru', 'ru-RU' => Locale::RUSSIAN,
            'it', 'it-IT' => Locale::ITALIAN,
            'id', 'id-ID' => Locale::INDONESIAN,
            'th', 'th-TH' => Locale::THAI,
            'vi', 'vt-VN' => Locale::VIET,
            default => null,
        };
    }
}
