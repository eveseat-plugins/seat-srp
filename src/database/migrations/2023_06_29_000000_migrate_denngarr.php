<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class MigrateDenngarr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('seat_srp_srp')) {
            DB::statement('INSERT cryptatech_seat_srp_srp SELECT * FROM seat_srp_srp');
            DB::statement('INSERT cryptatech_seat_srp_advrule SELECT * FROM denngarr_seat_srp_advrule');
            DB::statement('INSERT cryptatech_srp_insurances SELECT * FROM denngarr_srp_insurances');

            DB::update("update global_settings set name = 'cryptatech_seat_srp_webhook_url' where name= ?", ['denngarr_seat_srp_webhook_url']);
            DB::update("update global_settings set name = 'cryptatech_seat_srp_mention_role' where name= ?", ['denngarr_seat_srp_mention_role']);

            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_source' where name= ?", ['denngarr_seat_srp_advrule_def_source']);
            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_base' where name= ?", ['denngarr_seat_srp_advrule_def_base']);
            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_hull' where name= ?", ['denngarr_seat_srp_advrule_def_hull']);
            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_fit' where name= ?", ['denngarr_seat_srp_advrule_def_fit']);
            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_cargo' where name= ?", ['denngarr_seat_srp_advrule_def_cargo']);
            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_price_cap' where name= ?", ['denngarr_seat_srp_advrule_def_price_cap'])
            DB::update("update global_settings set name = 'cryptatech_seat_srp_advrule_def_ins' where name= ?", ['denngarr_seat_srp_advrule_def_ins'])
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No way to know what was added from this mig or from something else so do nothing.
        return;
    }
}
