<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductHasAttributeStock extends Model
{
    protected $guarded = [];
    protected $table = 'products_has_attribute_stocks';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
