<?php

namespace App\Services;

final class CacheHelperService
{
    /**
     * @param int|string $args
     *
     * @psalm-param 'poe1'|'poe2'|int $args
     */
    public static function generateCacheTag(string $pref, string|int ...$args): string
    {
        return sha1($pref . serialize($args));
    }

    public static function setHalfMinuteTtl(): int
    {
        return self::addSeconds(30);
    }

    public static function setMinuteTtl(): int
    {
        return self::addSeconds(60);
    }

    public static function setTenMinutesTtl(): int
    {
        return self::addSeconds(10 * 60);
    }

    public static function setHourTtl(): int
    {
        return self::addSeconds(60 * 60);
    }

    public static function setTenHourTtl(): int
    {
        return self::addSeconds(60 * 60 * 10);
    }

    public static function addSeconds(int $ttl): int
    {
        return $ttl + rand(1, 6);
    }
}
