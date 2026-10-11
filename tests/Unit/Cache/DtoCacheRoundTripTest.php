<?php

namespace Tests\Unit\Cache;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreTypeDto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The DataServices memoize DTO collections with the tagged cache. Laravel 13
 * hardens cache unserialization via config('cache.serializable_classes'), and
 * the array driver used by phpunit does not serialize at all — so this test
 * pins the behaviour against a driver that actually round-trips through
 * serialization.
 */
class DtoCacheRoundTripTest extends TestCase
{
    private const TAGS = ['round_trip_probe'];

    public function setUp(): void
    {
        parent::setUp();

        if (! in_array(config('cache.default'), ['redis', 'memcached'], true)) {
            $this->markTestSkipped('Requires a cache driver that serializes values.');
        }

        Cache::tags(self::TAGS)->flush();
    }

    private function dtoCollection(): \Illuminate\Support\Collection
    {
        $type = new PriceCheckCoreTypeDto(
            id: 1,
            created_at: '2024-01-01 00:00:00',
            updated_at: '2024-01-01 00:00:00',
            is_primary: true,
            rate: 2.5,
            type_id: 'divine',
            name: 'Divine Orb',
            price_check_core_id: 7,
        );

        $core = new PriceCheckCoreDto(
            id: 7,
            created_at: '2024-01-01 00:00:00',
            updated_at: '2024-01-01 00:00:00',
            price_check_core_types: [$type],
        );

        return collect([$core]);
    }

    public function test_dto_collection_survives_a_cache_round_trip(): void
    {
        Cache::tags(self::TAGS)->put('probe', $this->dtoCollection(), 60);
        $cached = Cache::tags(self::TAGS)->get('probe');

        $core = $cached->first();

        // Reading properties is what blows up when unserialization is blocked:
        // the objects come back as __PHP_Incomplete_Class.
        $this->assertInstanceOf(PriceCheckCoreDto::class, $core);
        $this->assertSame(7, $core->id);

        $type = $core->price_check_core_types[0];

        $this->assertInstanceOf(PriceCheckCoreTypeDto::class, $type);
        $this->assertSame('divine', $type->type_id);
        $this->assertSame('Divine Orb', $type->name);
        $this->assertTrue($type->is_primary);
        $this->assertSame(2.5, $type->rate);
    }
}
