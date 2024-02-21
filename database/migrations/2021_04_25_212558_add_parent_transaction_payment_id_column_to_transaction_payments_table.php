<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentTransactionPaymentIdColumnToTransactionPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->bigInteger('parent_transaction_payment_id')->nullable()->after('account_id');
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {

        });
    }
}