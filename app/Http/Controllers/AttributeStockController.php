<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Product;
use App\ProductHasAttribute;
use App\ProductHasAttributeStock;
use Illuminate\Http\Request;

class AttributeStockController extends Controller
{
    public function create($product_id)
    {
        $product = Product::where('id', $product_id)->select('id', 'name')->first();
        $product_qty_available = $this->getProductAvailableQuantity($product);

        $product['product_qty_available'] = $product_qty_available;

        $product_attributes_ids = ProductHasAttribute::where('product_id', $product_id)->pluck('attribute_id')->toArray();
        $size = Attribute::where('attribute_name', 'Size')->first();
        $color = Attribute::where('attribute_name', 'Color')->first();

        return view('product.add-attribute-stock', compact('product_attributes_ids', 'size', 'color', 'product'));
    }

    public function store(Request $request)
    {
        $attribute_stock_data = $request->only('product_id', 'stock');
        $attributes = $request->only('color', 'size');
        $product = Product::where('id', $attribute_stock_data['product_id'])->select('id')->first();
        $product_attribute_stock = ProductHasAttributeStock::where('product_id', $attribute_stock_data['product_id'])->sum('stock');
        $product_qty_available = $this->getProductAvailableQuantity($product);
        $total_product_attribute_stock_with_new_stock = $product_attribute_stock + $attribute_stock_data['stock'];
        if (empty($attributes['color'])) {
            $attribute = $attributes['size'];
        } elseif (empty($attributes['size'])) {
            $attribute = $attributes['color'];
        } else {
            $attribute = $attributes['color'] . ' ' . $attributes['size'];
        }
        $attribute_stock_data['attribute'] = $attribute;
        $product_has_attribute_stock = ProductHasAttributeStock::where([['product_id', $attribute_stock_data['product_id']], ['attribute', $attribute]])->select('attribute')->first();

        $stock_flag = 'Ok';
        if ($product_qty_available >= $total_product_attribute_stock_with_new_stock) {
            if (!empty($product_has_attribute_stock->attribute) == $attribute) {
                return 'Have';
            } else {
                ProductHasAttributeStock::create($attribute_stock_data);
                return $stock_flag;
            }
        } else {
            $stock_flag = 'Not Ok';
            return $stock_flag;
        }
    }

    public function update(Request $request, $attribute_stock_product_id)
    {
        $stock = $request->get('stock');
        $product_has_attribute_stock = ProductHasAttributeStock::find($attribute_stock_product_id);
        $product = Product::where('id', $product_has_attribute_stock->product_id)->select('id')->first();
        $product_qty_available = $this->getProductAvailableQuantity($product);
        $qty_flag = 'Have';
        if ($product_qty_available >= $stock) {
            $product_has_attribute_stock->update([
                'stock' => $stock
            ]);
            return $qty_flag;
        } else {
            $qty_flag = 'Not Have';
            return $qty_flag;
        }

    }

    public function destroy(Request $request)
    {
        if ($request->ajax()) {
            $attributes = $request->only('color', 'size');
            if (empty($attributes['color'])) {
                $attribute = $attributes['size'];
            } elseif (empty($attributes['size'])) {
                $attribute = $attributes['color'];
            } else {
                $attribute = $attributes['color'] . ' ' . $attributes['size'];
            }
            $product_has_attribute_stock = ProductHasAttributeStock::where('attribute', $attribute)->first();
            $stock_flag = 'Have';
            if (!empty($product_has_attribute_stock)) {
                $product_has_attribute_stock->delete();
                return $stock_flag;
            } else {
                $stock_flag = 'Not Have';
                return $stock_flag;
            }
        }
    }

    public function getProductAvailableQuantity($product)
    {
        $product_qty = $product->purchase_lines->sum('quantity');
        $product_qty_sold = $product->purchase_lines->sum('quantity_sold');
        $product_qty_adjusted = $product->purchase_lines->sum('quantity_adjusted');
        $product_qty_returned = $product->purchase_lines->sum('quantity_returned');
        return $product_qty - ($product_qty_sold + $product_qty_adjusted + $product_qty_returned);
    }

    public function refreshProductAttributeStockTable($product_id)
    {
        $products_has_attribute_stocks = ProductHasAttributeStock::where('product_id', $product_id)->get();
        $table_rows = '';
        foreach ($products_has_attribute_stocks as $products_has_attribute_stock) {
            $table_rows .= '<tr>
                                <td>' . $products_has_attribute_stock->attribute . '</td>
                                <td>' . $products_has_attribute_stock->stock . '</td>
                                <td>
                                    <div class="col-md-6">
                                        <input type="number" class="form-control"
                                               id="update_stock' . $products_has_attribute_stock->id . '" name="update_stock" value=""
                                               placeholder="Enter Qty">
                                        <input id="update_product_id' . $products_has_attribute_stock->id . '"
                                                   type="hidden" name="updae_product_id" value="' . $products_has_attribute_stock->product_id . '">
                                    </div>
                                    <button id="update-button" class="btn btn-primary"
                                            value="' . $products_has_attribute_stock->id . '"><i
                                                class="glyphicon glyphicon-arrow-up"></i> Update
                                    </button>
                                </td>
                            </tr>';
        }
        return $table_rows;
    }

}
