@forelse( $template as $value )

    @include('product.partials.variation_value_row', ['variation_index' => $row_index, 'value_index' => $loop->index, 'variation_name' => $value->vname, 'variation_value_id' => $value->vid,  'profit_percent' => $profit_percent])

@empty

    @include('product.partials.variation_value_row', ['variation_index' => $row_index, 'value_index' => 0, 'profit_percent' => $profit_percent])

@endforelse