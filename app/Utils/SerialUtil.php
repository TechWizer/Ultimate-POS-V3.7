<?php


namespace App\Utils;


use App\VariationLocationSerial;

class SerialUtil
{

    public function storeSerialNumber($serial_create)
    {
        try {
            $serial_number_exists = VariationLocationSerial::where('serial_number', $serial_create['serial_number'])->first();
            if (!empty($serial_number_exists)) {
                return 'Have';
            } else {
                VariationLocationSerial::create($serial_create);
                return 'Done';
            }
        } catch (\Exception $exception) {

        }
    }

//    public function deleteSerialNumber($serial_number_id)
//    {
//        $variation_location_serial = VariationLocationSerial::find($serial_number_id);
//        $variation_location_serial->delete();
//    }

}