<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index()
    {
        $attributes = Attribute::forDropdown();
        $all_attributes = Attribute::all();
        return view('attributes_values.index', compact('attributes', 'all_attributes'));
    }

    public function store(Request $request)
    {
        try {

            $attributes_values = $request->value;
            $attribute_val = [];
            foreach ($attributes_values as $attributes_value) {
                $attribute_val = AttributeValue::create([
                    'value' => $attributes_value['value'],
                    'attribute_id' => $request->attribute_id
                ]);

            }

            $output = ['success' => true,
                'data' => $attribute_val,
                'msg' => 'Attribute Added Success.!'
            ];

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => 'Something Went Wrong'
            ];
        }

        return back()->with('status', $output);
    }

    public function edit($id)
    {
        $attributes = Attribute::forDropdown();
        $attributes_values = AttributeValue::where('attribute_id', $id)->get();
        $attribute_id = $id;
        return view('attributes_values.edit', compact('attributes_values', 'attributes', 'attribute_id'));
    }

    public function update(Request $request, $id)
    {

        try {

            $attributes_values = AttributeValue::where('attribute_id', $id)->get();
            foreach ($attributes_values as $attributes_value) {
                $attributes_value->delete();
            }

            $attributes_values = $request->value;

            foreach ($attributes_values as $attributes_value) {
                AttributeValue::create([
                    'value' => $attributes_value['value'],
                    'attribute_id' => $id
                ]);

            }

            $output = ['success' => true,
                'msg' => 'Attribute Added Success.!'
            ];

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => 'Something Went Wrong'
            ];
        }

        return back()->with('status', $output);

    }

    public function destroy(Request $request, $id)
    {
        if ($request->ajax()) {
            $attributes_values = AttributeValue::where('attribute_id', $id)->get();
            foreach ($attributes_values as $attributes_value) {
                $attributes_value->delete();
            }
        }
    }

//    public function getAttributeValues($attribute_id)
//    {
//        if (\request()->ajax()) {
//            $attribute_values = AttributeValue::where('attribute_id', $attribute_id)->select('id', 'value')->get();
//            $html = '<option value="">Select</option>';
//            foreach ($attribute_values as $attribute_value) {
//                $html .= '<option value="' . $attribute_value->id . '">' . $attribute_value->value . '</option>';
//            }
//            return $html;
//        }
//    }

}
