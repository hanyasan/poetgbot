<?php

namespace Tests\Unit\NinjaService;

use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClientContract;
use App\Services\Mapper\MapperService;
use App\Services\PoeNinjaService\PoeNinjaService;
use Mockery;
use PHPUnit\Framework\TestCase;

class PoeNinjaLeagueMappingTest extends TestCase
{
    public function test_maps_poe1_leagues_without_hardcore_and_indexed(): void
    {
        $response = $this->service('{"economyLeagues":[{"name":"Allflame","url":"allflame","displayName":"Allflame"}]}')
            ->getAvailableLeagues(PoeVersionEnum::POE_1);

        $this->assertSame('Allflame', $response->economyLeagues[0]->name);
        $this->assertFalse($response->economyLeagues[0]->hardcore);
    }

    public function test_maps_poe2_leagues_with_hardcore_and_indexed(): void
    {
        $response = $this->service('{"economyLeagues":[{"name":"HC X","url":"x","displayName":"HC X","hardcore":true,"indexed":true}]}')
            ->getAvailableLeagues(PoeVersionEnum::POE_2);

        $this->assertTrue($response->economyLeagues[0]->hardcore);
        $this->assertTrue($response->economyLeagues[0]->indexed);
    }

    private function service(string $json): PoeNinjaService
    {
        $client = Mockery::mock(NinjaClientContract::class);
        $client->allows('getAvailableLeagues')->andReturn($json);

        return new PoeNinjaService($client, new MapperService());
    }
}
