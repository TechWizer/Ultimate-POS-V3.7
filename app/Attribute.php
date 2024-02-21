<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['attribute_name'];
    protected $table = 'attributes';

    public function attributes_values()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id', 'id');
    }

    public static function forDropdown()
    {
        return Attribute::select('id', 'attribute_name')->get();
    }
}
