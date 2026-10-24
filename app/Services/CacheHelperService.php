<?php

namespace App\Services;

final class CacheHelperService
{
    public const string CORE_TAG = 'price_check_core';
    public const string CORE_GET_TAG = 'price_check_core_get';
    public const string CORE_ID_TAG = 'price_check_core_id';
    public const string CORE_BY_LEAGUE_TAG = 'price_check_core_by_league';
    public const string LEAGUE_ID_TAG = 'poe_league_id';
    public const string LEAGUE_GET_TAG = 'poe_league_get';
    public const string LEAGUE_TAG = 'poe_league';
    public const string LEAGUE_VERSION_TAG = 'poe_league_version';
    public const string LEAGUE_NAME_TAG = 'poe_league_name';
    public const string CURRENCY_TYPE_ID_TAG = 'currency_type_id';
    public const string CURRENCY_TYPE_GET_TAG = 'currency_type_get';
    public const string CURRENCY_TYPE_TAG = 'currency_type';
    public const string CURRENCY_TYPE_BY_LEAGUE_TAG = 'currency_type_by_league';
    public const string CURRENCY_TYPE_BY_CORE_TAG = 'currency_type_by_core';

    /**
     * @param string $pref
     * @param string|int ...$args
     *
     * @return string
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
