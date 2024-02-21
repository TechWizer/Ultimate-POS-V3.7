<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manufacture extends Model
{
    use SoftDeletes;
    
    protected $guarded = ['id'];
 
    public static function forDropdown($business_id, $show_none = false)
    {
        $manufactures = Manufacture::where('business_id', $business_id)
                    ->pluck('name', 'id');

        if ($show_none) {
            $manufactures->prepend(__('lang_v1.none'), '');
        }

        return $manufactures;
    }
}
