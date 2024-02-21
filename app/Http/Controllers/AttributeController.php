<?php

namespace App\Http\Controllers;

use App\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::all();
        return view('attributes.index', compact('attributes'));
    }

    public function store(Request $request)
    {
        try {

            Attribute::create([
                'attribute_name' => $request->attribute_name
            ]);

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

    public function edit(Attribute $attribute)
    {
        return view('attributes.edit', compact('attribute'));
    }

    public function update(Attribute $attribute, Request $request)
    {
        try {

            $attribute->update([
                'attribute_name' => $request->attribute_name
            ]);

            $output = ['success' => true,
                'msg' => 'Attribute Updated Success.!'
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
            $attribute = Attribute::find($id);
            if ($attribute->attributes_values->count() > 0) {
                return 'No';
            } else {
                $attribute->delete();
                return 'Yes';
            }
        }
    }

}
