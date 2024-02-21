<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Add Product Codes</h4>
        </div>
        <form action="{{ action('ProductController@productCostCodeAndSellCodeUpdate') }}" method="post">
            @csrf
            @method('PATCH')
            <div class="modal-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Cost Price</th>
                        <th>Sell Price</th>
                        <th>Cost Code</th>
                        <th>Sell Code</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($variations as $key => $variation)
                        <tr>
                            <td>{{ $variation->name }}</td>
                            <td>{{ number_format($variation->dpp_inc_tax, 2) }}</td>
                            <td>{{ number_format($variation->sell_price_inc_tax, 2) }}</td>
                            <td>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input type="hidden" name="variations[{{$key}}][id]"
                                               value="{{ $variation->id }}">
                                        <input type="text" name="variations[{{ $key }}][cost_code]"
                                               value="{{ $variation->cost_code }}" class="form-control" id=""
                                               placeholder="Cost Code">
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input type="text" name="variations[{{ $key }}][sell_code]"
                                               value="{{ $variation->sell_code }}" class="form-control" id=""
                                               placeholder="Sell Code">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Name</th>
                        <th>Cost Price</th>
                        <th>Sell Price</th>
                        <th>Cost Code</th>
                        <th>Sell Code</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Product Codes</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
    <!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->
