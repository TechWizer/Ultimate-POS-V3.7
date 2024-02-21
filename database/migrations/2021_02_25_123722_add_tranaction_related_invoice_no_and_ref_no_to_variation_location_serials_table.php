<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTranactionRelatedInvoiceNoAndRefNoToVariationLocationSerialsTable extends Migration
{
    public function up()
    {
        Schema::table('variation_location_serials', function (Blueprint $table) {
            $table->unsignedInteger('invoice_no_transaction_id')->nullable()->after('status');
            $table->unsignedInteger('ref_no_transaction_id')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('variation_location_serials', function (Blueprint $table) {
            //
        });
    }
}