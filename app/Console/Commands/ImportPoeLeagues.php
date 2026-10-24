<?php

namespace App\Console\Commands;

use App\Enums\PoeVersionEnum;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use Illuminate\Console\Command;

/** @psalm-suppress UnusedClass, PropertyNotSetInConstructor */
final class ImportPoeLeagues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-poe-leagues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import current leagues tracked by poeNinja';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /** @var PoeLeagueServiceContract $service */
        $service = app(PoeLeagueServiceContract::class);

        $this->info('Starting import leagues');

        $service->upsertByNinja(PoeVersionEnum::POE_1);
        $service->upsertByNinja(PoeVersionEnum::POE_2);

        $this->info('Leagues imported');
    }
}
