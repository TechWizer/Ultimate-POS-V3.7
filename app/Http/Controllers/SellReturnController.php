<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\BusinessLocation;
use App\Cheque;
use App\ChequeTransaction;
use App\Transaction;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\TransactionSellLine;
use App\Events\TransactionPaymentDeleted;
use App\Exceptions\AdvanceBalanceNotAvailable;
use App\TransactionPayment;

class SellReturnController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $contactUtil;
    protected $businessUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ContactUtil $contactUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    
                    ->join(
                        'business_locations AS bl',
                        'transactions.location_id',
                        '=',
                        'bl.id'
                    )
                    ->join(
                        'transactions as T1',
                        'transactions.return_parent_id',
                        '=',
                        'T1.id'
                    )
                    ->leftJoin(
                        'transaction_payments AS TP',
                        'transactions.id',
                        '=',
                        'TP.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_return')
                    ->where('transactions.status', 'final')
                    ->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.invoice_no',
                        'contacts.name',
                        'transactions.final_total',
                        'transactions.payment_status',
                        'bl.name as business_location',
                        'T1.invoice_no as parent_sale',
                        'T1.id as parent_sale_id',
                        DB::raw('SUM(TP.amount) as amount_paid')
                    );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
            }

            $sells->groupBy('transactions.id');

            return Datatables::of($sells)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="#" class="btn-modal" data-container=".view_modal" data-href="{{action(\'SellReturnController@show\', [$parent_sale_id])}}"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</a></li>
                        <li><a href="{{action(\'SellReturnController@add\', [$parent_sale_id])}}" ><i class="fa fa-edit" aria-hidden="true"></i> @lang("messages.edit")</a></li>
                        <li><a href="{{action(\'SellReturnController@destroy\', [$id])}}" class="delete_sell_return" ><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</a></li>
                        <li><a href="#" class="print-invoice" data-href="{{action(\'SellReturnController@printInvoice\', [$id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a></li>

                    @if($payment_status != "paid")
                        <li><a href="{{action(\'TransactionPaymentController@addPayment\', [$id])}}" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.add_payment")</a></li>
                    @endif

                    <li><a href="{{action(\'TransactionPaymentController@show\', [$id])}}" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.view_payments")</a></li>
                    </ul>
                    </div>'
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('parent_sale', function ($row) {
                    return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . action('SellController@show', [$row->parent_sale_id]) . '">' . $row->parent_sale . '</button>';
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                )
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view")) {
                            return  action('SellReturnController@show', [$row->parent_sale_id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'parent_sale', 'payment_status', 'payment_due'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
      
        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sell_return.index')->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function create()
    // {
    //     if (!auth()->user()->can('sell.create')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $business_id = request()->session()->get('user.business_id');

    //     //Check if subscribed or not
    //     if (!$this->moduleUtil->isSubscribed($business_id)) {
    //         return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
    //     }

    //     $business_locations = BusinessLocation::forDropdown($business_id);
    //     //$walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

    //     return view('sell_return.create')
    //         ->with(compact('business_locations'));
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }
        $business_id = request()->session()->get('user.business_id');
        
                $transaction = Transaction::where('business_id', $business_id)
                                            ->with(['contact', 'location'])
                                            ->findOrFail($id);
             
                    $show_advance = in_array($transaction->type, ['sell', 'purchase']) ? true : false;
                    $payment_types = $this->transactionUtil->payment_types($transaction->location, $show_advance);
        
                    $paid_amount = $this->transactionUtil->getTotalPaid($id);
                    $amount = $transaction->final_total - $paid_amount;
                    if ($amount < 0) {
                        $amount = 0;
                    }
        
                    $amount_formated = $this->transactionUtil->num_f($amount);
        
                    $payment_line = new TransactionPayment();
                    $payment_line->amount = $amount;
                    $payment_line->method = 'cash';
                    $payment_line->paid_on = \Carbon::now()->toDateTimeString();
        
                    //Accounts
                    $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true);
                

        $sell = Transaction::where('business_id', $business_id)
                            ->with(['sell_lines', 'location', 'return_parent', 'contact', 'tax', 'sell_lines.sub_unit', 'sell_lines.product', 'sell_lines.product.unit'])
                            ->find($id);

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            $sell->sell_lines[$key]->formatted_qty = $this->transactionUtil->num_f($value->quantity, false, null, true);
        }

        return view('sell_return.add')
        ->with(compact('sell' ,'transaction', 'payment_types', 'payment_line', 'amount_formated', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
                }
        
                $user_id = $request->session()->get('user.id');

                DB::beginTransaction();

                $sell_return =  $this->transactionUtil->addSellReturn($input, $business_id, $user_id);

                $receipt = $this->receiptContent($business_id, $sell_return->location_id, $sell_return->id);
                
                $transaction_id = $request->input('transaction_id');
                $transaction = Transaction::where('business_id', $business_id)->with(['contact'])->findOrFail($transaction_id);
    
                $transaction_before = $transaction->replicate();

                if ($transaction->payment_status != 'paid') {
                    $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                    'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                    'cheque_number', 'bank_account_number']);
                     if ($inputs['method'] != 'cheque') {
                    $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
                    $inputs['transaction_id'] = $transaction->id;
                    $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                    $inputs['created_by'] = auth()->user()->id;
                    $inputs['payment_for'] = $transaction->contact_id;
    
                    if ($inputs['method'] == 'custom_pay_1') {
                        $inputs['transaction_no'] = $request->input('transaction_no_1');
                    } elseif ($inputs['method'] == 'custom_pay_2') {
                        $inputs['transaction_no'] = $request->input('transaction_no_2');
                    } elseif ($inputs['method'] == 'custom_pay_3') {
                        $inputs['transaction_no'] = $request->input('transaction_no_3');
                    }
    
                    if (!empty($request->input('account_id')) && $inputs['method'] != 'advance') {
                        $inputs['account_id'] = $request->input('account_id');
                    }
    
                    $prefix_type = 'purchase_payment';
                    if (in_array($transaction->type, ['sell', 'sell_return'])) {
                        $prefix_type = 'sell_payment';
                    } elseif (in_array($transaction->type, ['expense', 'expense_refund'])) {
                        $prefix_type = 'expense_payment';
                    }
    
                   
    
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    //Generate reference number
                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
    
                    $inputs['business_id'] = $request->session()->get('business.id');
                    $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');
    
                    //Pay from advance balance
                    $payment_amount = $inputs['amount'];
                    $contact_balance = !empty($transaction->contact) ? $transaction->contact->balance : 0;
                    if ($inputs['method'] == 'advance' && $inputs['amount'] > $contact_balance) {
                        throw new AdvanceBalanceNotAvailable(__('lang_v1.required_advance_balance_not_available'));
                    }
                    
                    if (!empty($inputs['amount'])) {
                        $tp = TransactionPayment::create($inputs);
                        $inputs['transaction_type'] = $transaction->type;
                        event(new TransactionPaymentAdded($tp, $inputs));
                    }
    
                    //update payment status
                    $payment_status = $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);
                    $transaction->payment_status = $payment_status;
                    
                    // $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
                }
                else {
    
                    $cheque_inputs['cheque_number'] = $request->get('cheque_number');
                    $cheque_inputs['cheque_date'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
                    $cheque_inputs['cheque_issued_date'] = $this->transactionUtil->uf_date($request->get('cheque_issued_date'), true);
                    $cheque_inputs['cheque_amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                    $cheque_inputs['cheque_status'] = 'due';
                    $cheque_inputs['cheque_type'] = $request->get('cheque_type');
                    $cheque_inputs['account_id'] = $request->input('account_id');
                    $cheque = Cheque::create($cheque_inputs);
    
                    ChequeTransaction::create([
                        'cheque_amount' =>  $this->transactionUtil->num_uf($inputs['amount']),
                        'cheque_id' => $cheque->id,
                        'transaction_id' => $transaction_id,
                        'contact_id' => $transaction->contact_id
                    ]);
    
                    $transaction->staff_note = 'Payment will be pay by cheque number : '.$request->get('cheque_number');
                    $transaction->update();
    
                    
                $cheque = Cheque::find($cheque->id);
                DB::beginTransaction();
                foreach ($cheque->cheque_transactions as $cheque_transaction) {
                    $transaction = Transaction::find($cheque_transaction->transaction_id);
                    $transaction_payment = TransactionPayment::where([['transaction_id', $transaction->id], ['business_id', $transaction->business_id]])->sum('amount');
                    $available_balance = ($transaction->final_total - $transaction_payment);
    //                create transaction payment
                    if ($transaction->payment_status != 'paid') {
                        if ($cheque_transaction->cheque_amount == $available_balance){
                            $payment = $available_balance;
                        }elseif ($cheque_transaction->cheque_amount > $available_balance){
                            $payment = $available_balance;
                        }else {
                            $payment = $cheque_transaction->cheque_amount;
                        }
                        $inputs['amount'] = $payment;
                        $inputs['method'] = 'cheque';
                        $inputs['note'] = 'Payment from cheque number: ' . $cheque->cheque_number;
                        $inputs['paid_on'] = now();
                        $inputs['transaction_id'] = $transaction->id;
                        $inputs['business_id'] = $transaction->business_id;
                        $inputs['created_by'] = auth()->user()->id;
                        $inputs['payment_for'] = $transaction->contact_id;
 
                        $inputs['cheque_id'] = $cheque->id;
    
                        $prefix_type = 'purchase_payment';
                        if ($transaction->type == 'sell') {
                            $prefix_type = 'sell_payment';
                        }
                        $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                        //Generate reference number
                        $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
    
                        if (!empty($inputs['amount'])) {
                            $transaction_payment = TransactionPayment::create($inputs);
   
                        }
                        //update payment status
                        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
    
    //                    add payment for account
                        $account_transaction_inputs['account_id'] = $cheque->account_id;
                        $type = 'debit';
                        if ($transaction->type == 'sell') {
                            $type = 'credit';
                        }
                        $account_transaction_inputs['type'] = $type;
                        $account_transaction_inputs['amount'] = $payment;
                        $account_transaction_inputs['operation_date'] = now();
                        $account_transaction_inputs['created_by'] = auth()->user()->id;
                        $account_transaction_inputs['transaction_id'] = $transaction->id;
                        $account_transaction_inputs['transaction_payment_id'] = $transaction_payment->id;
                        $account_transaction_inputs['cheque_id'] = $cheque->id;
                        $account_transaction_inputs['note'] = 'Payment from cheque number: ' . $cheque->cheque_number;
                        AccountTransaction::create($account_transaction_inputs);
    
                    }
                }
                $cheque->cheque_status = 'paid';
                $cheque->update();
            
    
                }
    
                    
                }
              
            }
            DB::commit();
           
            $output = ['success' => 1,
                        'msg' => __('lang_v1.success'),
                        'receipt' => $receipt
                    ];
        } catch (\Exception $e) {

            DB::rollBack();
        
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
           
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
          
                $msg = __('messages.something_went_wrong');
            }

            $output = ['success' => 0,
                            'msg' => $msg
                        ];
        }

        return $output;
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->with(
                                    'contact',
                                    'return_parent',
                                    'tax',
                                    'sell_lines',
                                    'sell_lines.product',
                                    'sell_lines.variations',
                                    'sell_lines.sub_unit',
                                    'sell_lines.product',
                                    'sell_lines.product.unit',
                                    'location'
                                )
                                ->first();

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $sell_taxes = [];
        if (!empty($sell->return_parent->tax)) {
            if ($sell->return_parent->tax->is_tax_group) {
                $sell_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->return_parent->tax, $sell->return_parent->tax_amount));
            } else {
                $sell_taxes[$sell->return_parent->tax->name] = $sell->return_parent->tax_amount;
            }
        }

        $total_discount = 0;
        if ($sell->return_parent->discount_type == 'fixed') {
            $total_discount = $sell->return_parent->discount_amount;
        } elseif ($sell->return_parent->discount_type == 'percentage') {
            $discount_percent = $sell->return_parent->discount_amount;
            if ($discount_percent == 100) {
                $total_discount = $sell->return_parent->total_before_tax;
            } else {
                $total_after_discount = $sell->return_parent->final_total - $sell->return_parent->tax_amount;
                $total_before_discount = $total_after_discount * 100 / (100 - $discount_percent);
                $total_discount = $total_before_discount - $total_after_discount;
            }
        }
        
        return view('sell_return.show')
            ->with(compact('sell', 'sell_taxes', 'total_discount'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('access_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                //Begin transaction
                DB::beginTransaction();

                $sell_return = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell_return')
                    ->with(['sell_lines', 'payment_lines'])
                    ->first();

                $sell_lines = TransactionSellLine::where('transaction_id', 
                                            $sell_return->return_parent_id)
                                    ->get();

                if (!empty($sell_return)) {
                    $transaction_payments = $sell_return->payment_lines;
                    
                    foreach ($sell_lines as $sell_line) {
                        if ($sell_line->quantity_returned > 0) {
                            $quantity = 0;
                            $quantity_before = $this->transactionUtil->num_f($sell_line->quantity_returned);

                            $sell_line->quantity_returned = 0;
                            $sell_line->save();

                            //update quantity sold in corresponding purchase lines
                            $this->transactionUtil->updateQuantitySoldFromSellLine($sell_line, 0, $quantity_before);

                            // Update quantity in variation location details
                            $this->productUtil->updateProductQuantity($sell_return->location_id, $sell_line->product_id, $sell_line->variation_id, 0, $quantity_before);
                        }
                    }

                    $sell_return->delete();
                    foreach ($transaction_payments as $payment) {
                        event(new TransactionPaymentDeleted($payment));
                    }
                }
                
                DB::commit();
                $output = ['success' => 1,
                            'msg' => __('lang_v1.success'),
                        ];
            } catch (\Exception $e) {
                DB::rollBack();

                if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                    $msg = $e->getMessage();
                } else {
                    \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                    $msg = __('messages.something_went_wrong');
                }

                $output = ['success' => 0,
                                'msg' => $msg
                            ];
            }

            return $output;
        }
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null
    ) {
        $output = ['is_enabled' => false,
                    'print_type' => 'browser',
                    'html_content' => null,
                    'printer_config' => [],
                    'data' => []
                ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        //Check if printing of invoice is enabled or not.
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
            
            //If print type browser - return the content, printer - return printer config data, and invoice format config
            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                $output['data'] = $receipt_details;
            } else {
                $output['html_content'] = view('sell_return.receipt', compact('receipt_details'))->render();
            }
        }

        return $output;
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];

                $business_id = $request->session()->get('user.business_id');
            
                $transaction = Transaction::where('business_id', $business_id)
                                ->where('id', $transaction_id)
                                ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, 'browser');

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }
}
