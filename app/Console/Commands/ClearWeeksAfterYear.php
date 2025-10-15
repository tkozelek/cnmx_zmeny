<?php

namespace App\Console\Commands;

use App\Models\Week;
use Illuminate\Console\Command;

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
        $count = Week::whereDate('date_to', '<', now()->subYear())->delete();
        $this->info($count.' of weeks older than one year have been deleted.');
    }
}
