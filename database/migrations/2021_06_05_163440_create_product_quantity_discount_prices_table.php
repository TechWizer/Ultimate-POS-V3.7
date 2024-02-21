<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductQuantityDiscountPricesTable extends Migration
{
    public function up()
    {
        Schema::create('product_quantity_discount_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('quantity_limit')->nullable();
            $table->decimal('price')->nullable();
            $table->unsignedInteger('product_id');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->index(['product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_quantity_discount_prices');
    }
}