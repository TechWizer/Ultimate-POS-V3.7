<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionPurchaseLineSerial extends Model
{
    protected $guarded = [];
    protected $table = 'transaction_purchase_line_serials';

    public function purchase_line()
    {
        return $this->belongsTo(PurchaseLine::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function variation_location_serial()
    {
        return $this->belongsTo(VariationLocationSerial::class);
    }

}