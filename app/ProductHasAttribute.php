<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductHasAttribute extends Model
{
    protected $guarded = [];
    protected $table = 'products_has_attributes';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
