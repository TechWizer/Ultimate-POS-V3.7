<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleModel extends Model
{
    use SoftDeletes;
    
    protected $guarded = ['id'];

    public static function forDropdown($business_id, $show_none = false)
    {
        $manufactures = VehicleModel::where('business_id', $business_id)
                    ->pluck('name', 'id');

        if ($show_none) {
            $manufactures->prepend(__('lang_v1.none'), '');
        }

        return $manufactures;
    }
    
    public static function forDropdownComp($business_id, $show_none = false)
    {
        $manufactures = VehicleModel::where('business_id', $business_id)
                    ->pluck('name', 'name');

        if ($show_none) {
            $manufactures->prepend(__('lang_v1.none'), '');
        }

        return $manufactures;
    }
}
