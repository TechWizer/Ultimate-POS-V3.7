<style>

	@media print {
		@page {
			size: 80mm size: portrait;
			margin: 0
		}
	}
	
	@page {
		margin: 0 size: 80mm;
		size: portrait;
	}
	
	table,
	tr,
	td,
	thead {
		border: none !important;
	}
	
	.small-text {
		font-size: 12px;
	}

	</style>
	<div class="text-center" style="margin-top: 4mm;">
		@if(!empty($receipt_details->logo))
		<img src="{{$receipt_details->logo}}" class="img img-responsive center-block" width="40%" style="margin-top: 5px; margin-bottom: 10px">
		@endif
		<p class="">
			<!-- Shop & Location Name  -->
			@if(!empty($receipt_details->display_name))
			<strong>
				{{$receipt_details->display_name}}
			</strong>
			@endif
			<br>
			@if(!empty($receipt_details->address))
			<small class="text-center">
				{!! $receipt_details->address !!}
			</small>
			@endif
			<br>
			<small class="text-center">
				@if(!empty($receipt_details->contact))
				{{ $receipt_details->contact }}
				@endif
				@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
				,
				@endif
				@if(!empty($receipt_details->website))
				{{ $receipt_details->website }}
				@endif
				<br>
			</small>
		</p>
	</div>
	<div style="margin-left: 6mm; margin-right: 6mm">
		<span style="font-size: 10px">
			@if(!empty($receipt_details->invoice_no_prefix))
			<b>{!! $receipt_details->invoice_no_prefix !!}</b>
			@endif
			{{$receipt_details->invoice_no}}
		</span>
		@if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
		<p>
			<span class="pull-left text-left" style="font-size: 10px">
				@if(!empty($receipt_details->table_label))
				<b>{!! $receipt_details->table_label !!}</b>
				@endif
				{{$receipt_details->table}}
			</span>
		</p>
		@endif
		<p style="font-size: 10px">
			<!-- customer info -->
			@if(!empty($receipt_details->customer_name))
			<b>{{ $receipt_details->customer_label }}</b> {{ $receipt_details->customer_name }}
			@endif
			<br>
			<b>Cashier</b> {{ $receipt_details->sales_person }}
		</p>
		<span style="font-size: 10px">
			<b>{{$receipt_details->date_label}}</b> {{$receipt_details->invoice_date}}
		</span>
		<hr style="border: 1px solid black;">
		<table style="width:100%">
			<thead>
				<tr>
					<th class="description" style="font-size: 10px">Description</th>
					<th class="text-right" style="font-size: 10px">QTY. </th>
					<th class="text-right" style="font-size: 10px"> Price</th>
					<th class="text-right" style="font-size: 10px">Total</th>
				</tr>
			</thead>
			<tbody>
				@foreach($receipt_details->lines as $line)
				<tr>
					<td class="description" style="">{{$line['name']}} {{$line['variation']}} @if(!empty($modifier['sub_sku'])),
						{{$modifier['sub_sku']}} @endif
						@if(!empty($line['sell_line_note']))
						<small>({{$line['sell_line_note']}})</small>
						@endif 
					</td>
					<td class="text-right">{{$line['quantity']}}</td>
					<td class="text-right">{{$line['unit_price_inc_tax']}}</td>
					<td class="text-right">{{$line['line_total']}}</td>
				</tr>
				@endforeach
				{{-- <tr>
					<td>&nbsp</td>
					<td>&nbsp</td>
					<td>&nbsp</td>
					<td>&nbsp</td>
				</tr> --}}
				<tr>
					<td colspan="4">
						<hr style="border: 1px solid black;">
					</td>
				</tr>
				<tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price small-text"> <strong>SUBTOTAL </strong></td>
					<td class="text-right"> {{$receipt_details->subtotal}}</td>
				</tr>
				<tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price small-text"><strong>DISCOUNT </strong></td>
					<td class="text-right"> {{$receipt_details->discount}}</td>
				</tr>
				<tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price small-text" style=""><strong> TOTAL </strong></td>
					<td class="text-right" style=""> {{$receipt_details->total}}</td>
				</tr>
				<tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price small-text"><strong> PAID </strong></td>
					<td class="text-right"> {{!empty($receipt_details->total_paid)?$receipt_details->total_paid:0}} </td>
				</tr>
				<tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price small-text"><strong> DUE </strong></td>
					<td class="text-right"> {{!empty($receipt_details->total_due)?$receipt_details->total_due:0}} </td>
				</tr>
				{{-- <tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price"><strong> TENDERED </strong></td>
					<td class="text-right"> {{$receipt_details->cash_tendered}} </td>
				</tr>
				<tr>
					<td class="description"></td>
					<td class="quantity"></td>
					<td class="price"><strong> CHANGE </strong></td>
					<td class="text-right"> {{$receipt_details->change_return}} </td>
				</tr> --}}
			</tbody>
		</table>
		<hr style="border: 1px solid black;">
	</div>
	@if(!empty($receipt_details->footer_text))
	<div class="text-center">
			{!! $receipt_details->footer_text !!}
	</div>
	@endif
	<div class="text-center">
		<small class="text-center" style="font-fize:8px">POS BY TECHWIZER</small>
	</div>
