<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Canonical QuizSnap brand image paths (logo + favicons).
 */
final class BrandAssets
{
    public const LOGO_PATH = 'images/logo.png';

    public const LOGO_ALT = 'QuizSnap Logo';

    public const OG_IMAGE_PATH = 'images/og-image.png';

    public static function logoUrl(): string
    {
        return asset(self::LOGO_PATH);
    }

    public static function logoAlt(): string
    {
        $name = trim((string) Setting::getValue(Setting::KEY_APP_NAME, config('app.name', 'QuizSnap')));

        return $name !== '' ? $name.' Logo' : self::LOGO_ALT;
    }

    /** Institution override when set; otherwise the system QuizSnap logo. */
    public static function markUrl(): string
    {
        $custom = Setting::institutionLogoUrl();

        return $custom !== '' ? $custom : self::logoUrl();
    }

    public static function ogImageUrl(): string
    {
        return asset(self::OG_IMAGE_PATH);
    }
}
