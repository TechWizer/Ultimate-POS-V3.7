@extends('layouts.app')
@section('title', __('lang_v1.edit_purchase_return'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
<br>
    <h1>@lang('lang_v1.edit_purchase_return')</h1>
</section>

<!-- Main content -->
<section class="content no-print">
	{!! Form::open(['url' => action('CombinedPurchaseReturnController@update'), 'method' => 'post', 'id' => 'purchase_return_form', 'files' => true ]) !!}
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-3">
					<div class="form-group">
						<input type="hidden" name="purchase_return_id" value="{{$purchase_return->id}}">
						<input type="hidden" id="location_id" value="{{$purchase_return->location_id}}">
						{!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-user"></i>
							</span>
							{!! Form::select('contact_id', [ $purchase_return->contact_id => $purchase_return->contact->name], $purchase_return->contact_id, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'supplier_id']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
						{!! Form::text('ref_no', $purchase_return->ref_no, ['class' => 'form-control']); !!}
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime($purchase_return->transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-3">
	                <div class="form-group">
	                    {!! Form::label('document', __('purchase.attach_document') . ':') !!}
	                    {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
	                    <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
	                    @includeIf('components.document_help_text')</p>
	                </div>
	            </div>
			</div>
		</div>
	</div> <!--box end-->
	<div class="box box-solid">
		<div class="box-header">
        	<h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
       	</div>
		<div class="box-body">
			<div class="row">
				<div class="col-sm-8 col-sm-offset-2">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-search"></i>
							</span>
							{!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_purchase_return', 'placeholder' => __('stock_adjustment.search_products')]); !!}
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<input type="hidden" id="total_amount" name="final_total" value="{{$purchase_return->final_total}}">
					<div class="table-responsive">
					<table class="table table-bordered table-striped table-condensed" 
					id="purchase_return_product_table">
						<thead>
							<tr>
								<th class="text-center">	
									@lang('sale.product')
								</th>
								@if(session('business.enable_lot_number'))
									<th>
										@lang('lang_v1.lot_number')
									</th>
								@endif
								@if(session('business.enable_product_expiry'))
									<th>
										@lang('product.exp_date')
									</th>
								@endif
								<th class="text-center">
									@lang('sale.qty')
								</th>
								<th class="text-center">
									@lang('sale.unit_price')
								</th>
								<th class="text-center">
									@lang('sale.subtotal')
								</th>
								<th class="text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody>
							@foreach($purchase_lines as $purchase_line)
								@include('purchase_return.partials.product_table_row', ['product' => $purchase_line, 'row_index' => $loop->index, 'edit' => true])

								@php
									$row_index = $loop->iteration;
								@endphp
							@endforeach
						</tbody>
					</table>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-md-4">
					<input type="hidden" id="product_row_index" value="{{$row_index}}">
					<div class="form-group">
						{!! Form::label('tax_id', __('purchase.purchase_tax') . ':') !!}
						<select name="tax_id" id="tax_id" class="form-control select2" placeholder="'Please Select'">
							<option value="" data-tax_amount="0" data-tax_type="fixed" selected>@lang('lang_v1.none')</option>
							@foreach($taxes as $tax)
								<option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" data-tax_type="{{ $tax->calculation_type }}" @if($purchase_return->tax_id == $tax->id) selected @endif>{{ $tax->name }}</option>
							@endforeach
						</select>
						{!! Form::hidden('tax_amount', $purchase_return->tax_amount, ['id' => 'tax_amount']); !!}
					</div>
				</div>
				<div class="col-md-8">
					<div class="pull-right"><b>@lang('stock_adjustment.total_amount'):</b> <span id="total_return" class="display_currency">{{$purchase_return->final_total}}</span></div>
				</div>
			</div>
		</div>
	</div> <!--box end-->
	<div class="box box-solid">
		<div class="box-header">
			<h3 class="box-title">@lang('purchase.purchase_invoices')</h3>
		</div>
		<div class="box-body">
			<div class="row">
				<div class="col-sm-8 col-sm-offset-2">
					<div class="form-group">
						<div class="">
							<label class="mb-3">@lang('purchase.purchase_invoices')</label>
							<select class="select2 form-control mb-3 select2-multiple" multiple="multiple" data-placeholder="Select Purchase Invoices" name="purchase_invoice_edit_ids[]" id="purchase_invoice_edit_ids" style="width: 100%; height:36px;">
								@foreach($purchase_invoices as $purchase_invoice)
									<option value="{{ $purchase_invoice->id }}" {{ in_array($purchase_invoice->id, $purchase_returned_purchase_invoice_ids)?'selected':'' }}>{{ $purchase_invoice->ref_no }} - Rs.{{number_format($purchase_invoice->final_total - $purchase_invoice->paid_amount, 2)}}</option>
								@endforeach
							</select>
						</div><!-- end col -->
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					{{--                    <input type="hidden" id="purchase_row_index" value="0">--}}
					<input type="hidden" id="total_purchase_amount" name="final_purchase_total" value="0">
					<div class="table-responsive">
						<table class="table table-bordered table-striped table-condensed"
							   id="purchase_invoice_table">
							<thead>
							<tr>
								<th class="text-center">
									@lang('purchase.ref_no')
								</th>
								<th class="text-center">
									@lang('purchase.invoice_amount')
								</th>
								<th class="text-center">
									@lang('purchase.pay_amount')
								</th>
								<th class="text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
							</thead>
							<tbody id="purchase_invoice_table_tbody">
							@foreach($purchase_return->payment_lines as $payment_line)
								<tr>
									<td>
										<input type="hidden" name="purchase_invoices[{{$payment_line->id}}][id]" value="{{$payment_line->id}}">
										<input type="hidden" name="purchase_invoices[{{$payment_line->id}}][transaction_id]" value="{{$payment_line->transaction_id}}">
										<input type="hidden" name="purchase_invoices[{{ $payment_line->id }}][ref_no]" value="{{$payment_line->payment_ref_no}}">
										{{ $payment_line->payment_ref_no }}
									</td>
									@php
										$parent_payment = \App\TransactionPayment::where('id', $payment_line->parent_transaction_payment_id)->select('transaction_id')->first();
										$purchase = \App\Transaction::leftJoin('transaction_payments', 'transaction_payments.transaction_id', '=', 'transactions.id')
                        					->where('transactions.type', 'purchase')
                        					->where('transactions.id', $parent_payment->transaction_id)
                        					->select(
                        					    'transactions.id',
                        					    'transactions.ref_no',
                        					    'transactions.final_total',
                        					    DB::raw('SUM(transaction_payments.amount) AS paid_amount')
                        					)->groupBy('transactions.id')->first();
									@endphp
									<td><input type="hidden" id="final_total{{ $payment_line->id }}" name="purchase_invoices[{{ $payment_line->id }}][final_total]" value="{{ $purchase->final_total-$purchase->paid_amount+$payment_line->amount }}" >Rs.{{number_format($purchase->final_total-$purchase->paid_amount+$payment_line->amount, 2)}}</td>
									<td>
										<div class="form-group">
											<input type="text" class="form-control purchase_invoice_pay_amount" id="{{ $payment_line->id }}" name="purchase_invoices[{{$payment_line->id}}][pay_amount]" value="{{ $payment_line->amount }}" placeholder="Input Amount">
										</div>
									</td>
									<td class="text-center">
										<i class="fa fa-trash remove_purchase_row cursor-pointer" aria-hidden="true"></i>
									</td>
								</tr>
							@endforeach
							</tbody>
						</table>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-md-12">
					<div class="pull-right"><b>@lang('purchase.total_purchase_amount'):</b> <span id="total_purchase_amount_display">0.00</span></div>
					<br>
					<div class="pull-right"><b>@lang('purchase.total_available_amount'):</b> <span id="total_available_amount_display">0.00</span></div>
				</div>
			</div>
		</div>
	</div> <!--box end-->
	<div class="row">
		<div class="col-md-12">
			<button type="button" id="submit_purchase_return_form" class="btn btn-primary pull-right btn-flat">@lang('messages.update')</button>
		</div>
	</div>
	{!! Form::close() !!}
</section>
@stop
@section('javascript')
	<script src="{{ asset('js/purchase_return.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		__page_leave_confirmation('#purchase_return_form');
	</script>
@endsection
