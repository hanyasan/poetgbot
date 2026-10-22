<?php

namespace Tests\Feature\League;

use App\DataTransferObjects\RepositoryObjects\League\LeagueCheckRuleDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Models\User;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class LeagueControllerTest extends TestCase
{
    private MockInterface|PoeLeagueServiceContract $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->mock(PoeLeagueServiceContract::class);
    }

    private function league(
        int $id,
        string $name,
        PoeVersionEnum $version,
        ?LeagueCheckRuleDto $rule = null,
    ): PoeLeagueDto {
        return new PoeLeagueDto(
            $id,
            Carbon::now()->toDateTimeString(),
            Carbon::now()->toDateTimeString(),
            $name,
            $version->value,
            true,
            $rule,
        );
    }

    private function rule(int $id, ?int $minutesCheck): LeagueCheckRuleDto
    {
        return new LeagueCheckRuleDto(
            $id,
            $minutesCheck,
            Carbon::now()->toDateTimeString(),
            Carbon::now()->toDateTimeString(),
        );
    }

    /**
     * The JWT guard only needs a user instance to satisfy auth:api; actingAs
     * sets it on the guard, so no database row is required.
     */
    private function authenticated(): self
    {
        $this->actingAs(new User, 'api');

        return $this;
    }

    public function test_index_requires_authentication(): void
    {
        $this->service->expects('getAllActive')->never();

        $this->getJson('/api/v1/league')->assertUnauthorized();
    }

    public function test_index_returns_active_leagues(): void
    {
        $rule = $this->rule(5, 60);
        $league = $this->league(1, 'Standard', PoeVersionEnum::POE_2, $rule);

        $this->service->expects('getAllActive')->once()->andReturns(new Collection([$league]));

        $this->authenticated()
            ->getJson('/api/v1/league')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'created_at' => $league->created_at,
                        'updated_at' => $league->updated_at,
                        'league_name' => 'Standard',
                        'poe_version' => 'poe2',
                        'is_active' => true,
                        'check_rule' => [
                            'id' => 5,
                            'created_at' => $rule->created_at,
                            'updated_at' => $rule->updated_at,
                            'minutes_check' => 60,
                        ],
                    ],
                ],
            ]);
    }

    public function test_index_returns_league_without_check_rule_as_null(): void
    {
        $league = $this->league(1, 'Standard', PoeVersionEnum::POE_1);

        $this->service->expects('getAllActive')->once()->andReturns(new Collection([$league]));

        $this->authenticated()
            ->getJson('/api/v1/league')
            ->assertOk()
            ->assertJsonPath('data.0.check_rule', null);
    }

    public function test_index_returns_empty_data_when_no_active_leagues(): void
    {
        $this->service->expects('getAllActive')->once()->andReturns(new Collection);

        $this->authenticated()
            ->getJson('/api/v1/league')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
