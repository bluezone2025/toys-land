<?php

namespace App\Support;

class UserAgent
{
    public static function isBot(?string $ua): bool
    {
        return ! $ua || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless|lighthouse/i', $ua) === 1;
    }

    public static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet|(android(?!.*mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }

    public static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/edg\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua) => 'Samsung',
            (bool) preg_match('/chrome|crios/i', $ua) => 'Chrome',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Other',
        };
    }

    public static function platform(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/windows/i', $ua) => 'Windows',
            (bool) preg_match('/mac os/i', $ua) => 'macOS',
            (bool) preg_match('/linux/i', $ua) => 'Linux',
            default => 'Other',
        };
    }
}
