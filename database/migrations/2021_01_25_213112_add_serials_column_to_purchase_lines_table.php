<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSerialsColumnToPurchaseLinesTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->text('serials')->nullable()->after('sub_unit_id');
        });
    }

    public function down()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            //
        });
    }
}