<?php

namespace App\Console\Commands;

use App\Jobs\ScanOffersJob;
use Illuminate\Console\Command;

class ScanOlxOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'olx:scan-offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan OLX offers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dispatch(new ScanOffersJob());
    }
}


