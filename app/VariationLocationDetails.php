<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariationLocationDetails extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    protected $table = 'variation_location_details';

    public function variation_location_serials()
    {
        return $this->hasMany(VariationLocationSerial::class);
    }
}
