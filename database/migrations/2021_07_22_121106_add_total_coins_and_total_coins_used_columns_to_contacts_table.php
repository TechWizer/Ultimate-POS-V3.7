<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalCoinsAndTotalCoinsUsedColumnsToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('contacts', function (Blueprint $table) {
            $table->decimal('total_coins_used')->nullable()->default(0)->after('total_rp_expired');
            $table->decimal('total_coins')->nullable()->default(0)->after('total_rp_expired');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
        });
    }
}
