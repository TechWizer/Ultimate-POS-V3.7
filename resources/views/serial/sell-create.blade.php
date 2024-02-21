<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Serial Number</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-5">
                    <div class="form-group">
                        <label for="serial_no">Serial Number</label>
                        <input type="text" class="form-control" id="serial_no_sell" name="serial_no"
                               placeholder="Enter Serial Number"><span>Added Serial Number: <b id="length">0</b></span>
                        <input id="product_id" type="hidden" value="{{ $product_id }}">
                        <input id="product_qty" type="hidden" value="{{ $quantity }}">
                        <input id="row_id" type="hidden" value="{{ $row_id }}">
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <br>
                        <button style="margin-top: 2px;" type="button" class="btn btn-primary add_sell_serials_create"><i
                                    class="fa fa-plus"></i> Add Serial
                        </button>
                        <button disabled id="view-serials" style="margin-top: 2px;" type="button" class="btn btn-primary view-added-serials"><i
                                    class="fa fa-archive"></i> View Serials
                        </button>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <br>
                        <h4>Total Qty: <b>{{ !empty($quantity)?number_format($quantity):'0' }}</b></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="serial-modal-done" type="button" class="btn btn-primary">Done</button>
            <button id="serial-modal-close" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
    <!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->