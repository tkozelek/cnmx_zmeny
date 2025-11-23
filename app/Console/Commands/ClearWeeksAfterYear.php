<?php

namespace App\Console\Commands;

use App\Models\Week;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearWeeksAfterYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-weeks-after-year';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vyčisti všetky data z weeks, ktoré sú staršie ako rok.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Week::whereDate('date_to', '<', now()->subYears(1)->subMonths(6))->delete();
        $this->info(now()->format('Y-m-d H:i:s').' '.$count.' of weeks older than one year have been deleted.');

        Log::info($count.' of weeks older than one year have been deleted.');

        return parent::SUCCESS;
    }
}
