<?php

namespace Tests\Unit\Repository\PoeLeague;

use App\Enums\PoeVersionEnum;
use App\Models\PoeLeague;
use App\Repositories\PoeLeague\LeagueRuleRepositoryContract;
use Tests\TestCase;

class LeagueRuleRepositoryTest extends TestCase
{
    private LeagueRuleRepositoryContract $leagueRuleRepository;
    private PoeLeague $league1;
    private PoeLeague $league2;

    public function setUp(): void
    {
        parent::setUp();

        $this->league1 = PoeLeague::query()->create([
            'league_name' => 'League 1',
            'poe_version' => PoeVersionEnum::POE_1,
            'is_active' => true,
        ]);
        $this->league2 = PoeLeague::query()->create([
            'league_name' => 'League 2',
            'poe_version' => PoeVersionEnum::POE_1,
            'is_active' => true,
        ]);

        $this->leagueRuleRepository = app(LeagueRuleRepositoryContract::class);
    }

    public function test_update_or_create_creates_rule(): void
    {
        $this->leagueRuleRepository->updateOrCreate(11, $this->league1->id);

        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league1->id, 'minutes_check' => 11]);
    }

    public function test_update_or_create_updates_rule(): void
    {
        $this->leagueRuleRepository->updateOrCreate(11, $this->league1->id);

        $this->leagueRuleRepository->updateOrCreate(null, $this->league1->id);

        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league1->id, 'minutes_check' => null]);
    }

    public function test_upsert_defaults_for_leagues_creates_rule(): void
    {
        $this->leagueRuleRepository->upsertDefaultForLeagues([$this->league1->id, $this->league2->id]);

        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league1->id]);
        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league1->id, 'minutes_check' => config('price_check.default_hours_check') * 60]);
        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league2->id]);
        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league2->id, 'minutes_check' => config('price_check.default_hours_check') * 60]);
    }

    public function test_upsert_defaults_for_leagues_updates_rule(): void
    {
        $this->leagueRuleRepository->updateOrCreate(null, $this->league1->id);

        $this->leagueRuleRepository->upsertDefaultForLeagues([$this->league1->id, $this->league2->id]);

        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league1->id]);
        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league1->id, 'minutes_check' => config('price_check.default_hours_check') * 60]);
        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league2->id]);
        $this->assertDatabaseHas('league_check_rules', ['poe_league_id' => $this->league2->id, 'minutes_check' => config('price_check.default_hours_check') * 60]);
    }

    public function test_upsert_defaults_for_leagues_can_take_empty_array(): void
    {
        $this->leagueRuleRepository->upsertDefaultForLeagues([]);

        $this->assertTrue(true);
    }
}
