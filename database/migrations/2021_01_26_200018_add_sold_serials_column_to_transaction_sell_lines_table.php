<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoldSerialsColumnToTransactionSellLinesTable extends Migration
{
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->text('sold_serials')->nullable()->after('sub_unit_id');
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            //
        });
    }
}