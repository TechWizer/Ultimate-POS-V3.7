<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariationLocationSerial extends Model
{
    protected $guarded = [];
    protected $table = 'variation_location_serials';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation_location_detail()
    {
        return $this->belongsTo(VariationLocationDetails::class);
    }

    public function transaction_purchase_line_serials()
    {
        return $this->hasMany(TransactionPurchaseLineSerial::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

}