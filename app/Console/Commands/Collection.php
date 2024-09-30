<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Collection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'momo:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('COLLECTION REQUETE EN COURS.');

        \App\MomoApi\Src\Facades\MomoApi::collection();

        $this->info('COLLECTION REQUETE TERMINEE');
    }
}
