<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 29/12/2017
 * Time: 19:51.
 */

namespace CryptaTech\Seat\SeatSrp\Commands;

use CryptaTech\Seat\SeatSrp\Jobs\UpdateEsiInsurance;
use Illuminate\Console\Command;

class InsuranceUpdate extends Command
{

    protected $signature = 'esi:insurances:update';

    protected $description = 'Queue a job which will refresh insurances data';

    public function handle()
    {
        UpdateEsiInsurance::dispatch();
    }
}
