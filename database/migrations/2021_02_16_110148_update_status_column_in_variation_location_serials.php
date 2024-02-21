<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusColumnInVariationLocationSerials extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variation_location_serials', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('variation_location_serials', function (Blueprint $table) {
            $table->enum('status', ['available', 'sold', 'returned', 'stock_adjusted'])->default('available')->after('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variation_location_serials', function (Blueprint $table) {
            //
        });
    }
}
