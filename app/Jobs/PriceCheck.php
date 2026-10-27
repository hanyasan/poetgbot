<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Artisan;

#[Tries(1)]
final class PriceCheck implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('scheduled');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call('app:import-picked');
    }
}
