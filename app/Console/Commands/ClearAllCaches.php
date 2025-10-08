<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:allclear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vymaže všetky caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');

        $this->info('Všetky cache boli vymazané!');
    }
}
