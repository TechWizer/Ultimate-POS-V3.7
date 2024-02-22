<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Contact;
use App\ProductHasAttribute;
use App\ProductHasAttributeStock;
use App\ProductQuantityDiscountPrice;
use App\User;
use Illuminate\Http\Request;
use Hash;
use App\CashRegister;
use DB;
use App\Utils\CashRegisterUtil;
use App\Product;
use App\Account;
use App\AccountTransaction;
use App\Brands;
use App\Unit;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\CustomerGroup;
use App\Media;
use App\RegisterExpense;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;

use Illuminate\Support\Facades\Storage;
use App\Utils\Util;
use App\VariationGroupPrice;

class APIController extends Controller
{

    protected $cashRegisterUtil;
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $commonUtil;

    public function __construct(
        CashRegisterUtil $cashRegisterUtil,
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil,
        Util $commonUtil,
        NotificationUtil $notificationUtil
    ) {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->payment_types = ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'];
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->commonUtil = $commonUtil;

        $this->dummyPaymentLine = [
            'method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''
        ];
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (\Auth::attempt($credentials)) {
            // Authentication passed...
            return \Auth::user();
        }
        return [];
    }


    public function getbusiness($id)
    {

        $business = Business::where('id', $id)->first();

        return $business;
    }

    public function loadData()
    {

        $customers = Contact::where('business_id', 1)
            ->whereIn('type', ['customer', 'both'])->get();

        return $customers;
    }

    public function loadProducts()
    {

        $products = \App\Product::with('Variations')->with('PurchaseLine')->get()->toArray();

        return $products;
    }

    public function OpenedRegister($user)
    {
        $user_data = DB::table('users')->where('username', $user)->first();


        $count = CashRegister::where('user_id', $user_data->id)
            ->where('status', 'open')
            ->count();
        return $count;
    }

    public function OpenedRegisterID($user)
    {
        $count = CashRegister::where('user_id', $user)
            ->where('status', 'open')
            ->get()
            ->last();

        return $count->id;
    }

    public function saveRegisterExpense(Request $request)
    {


        $data = RegisterExpense::create($request->all());

        return "SAVED";
    }

    public function TotalRegisterExpense($id)
    {

        $total = RegisterExpense::where('cash_register_id', $id)->sum('amount');
        return $total;
    }

    public function ViewRegisterExpense($id)
    {

        $data = RegisterExpense::where('cash_register_id', $id)->get();

        return $data;
    }

    public function getCloseRegister($id, $user_id)
    {
        $register_details = $this->cashRegisterUtil->getRegisterDetails($id);

        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);

        return $register_details;
    }


    public function openRegister(Request $request)
    {


        try {
            $initial_amount = 0;
            if (!empty($request->get('amount'))) {
                $initial_amount = $this->cashRegisterUtil->num_uf($request->get('amount'));
            }
            $user_id = DB::table('users')->where('username', $request->get('username'))->first()->id;
            $business_id = $request->get('business_id');
            $register = CashRegister::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'status' => 'open'
            ]);
            $register->cash_register_transactions()->create([
                'amount' => $initial_amount,
                'pay_method' => 'cash',
                'type' => 'credit',
                'transaction_type' => 'initial'
            ]);
            return "DONE";
        } catch (\Exception $e) {

            //return $e;
            return "eror";
        }
    }

    public function getWalkInCustomer($business_id)
    {
        $contact = Contact::where('type', 'customer')
            ->where('business_id', $business_id)
            ->where('is_default', 1)
            ->first()
            ->toArray();

        if (!empty($contact)) {
            return $contact;
        } else {
            return false;
        }
    }

    public function getCustomers(Request $request)
    {

        //        return $request;

        $term = $request->get('query');

        $business_id = $request->get('business_id');
        $user_id = $request->get('user_id');

        $contacts = Contact::where('business_id', $business_id);

        $selected_contacts = User::isSelectedContacts($user_id);
        if ($selected_contacts) {
            $contacts->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
                ->where('uca.user_id', $user_id);
        }

        if (!empty($term)) {
            $contacts->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('supplier_business_name', 'like', '%' . $term . '%')
                    ->orWhere('mobile', 'like', '%' . $term . '%')
                    ->orWhere('contacts.contact_id', 'like', '%' . $term . '%');
            });
        }

        $contacts = $contacts->select(
            'contacts.id',
            DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')')) AS text"),
            'mobile',
            'landmark',
            'city',
            'state',
            'pay_term_number',
            'pay_term_type'
        )
            ->onlyCustomers()
            ->get()
            ->toArray();
        return $contacts;
    }

    public function getAllCustomers(Request $request)
    {

        $business_id = $request->get('business_id');
        $user_id = $request->get('user_id');

        $contacts = Contact::where('business_id', $business_id);

        $selected_contacts = User::isSelectedContacts($user_id);
        if ($selected_contacts) {
            $contacts->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
                ->where('uca.user_id', $user_id);
        }

        $contacts = $contacts->select(
            'contacts.id',
            DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')')) AS text"),
            'mobile',
            'landmark',
            'city',
            'state',
            'pay_term_number',
            'pay_term_type',
            'balance',
            'total_rp',
            'total_rp_used',
            'total_rp_expired',
            'total_coins'
        )
            ->onlyCustomers()
            ->get();

        foreach ($contacts as $contact) {
            $contact->customer_group = $this->contactUtil->getCustomerGroup($business_id, $contact['id']);
        }

        $contacts->toArray();

        return $contacts;
    }

    public function getSuppliers(Request $request)
    {

        //        return $request;

        $term = $request->get('query');

        $business_id = $request->get('business_id');
        $user_id = $request->get('user_id');

        $contacts = Contact::where('business_id', $business_id);

        $selected_contacts = User::isSelectedContacts($user_id);
        if ($selected_contacts) {
            $contacts->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
                ->where('uca.user_id', $user_id);
        }

        if (!empty($term)) {
            $contacts->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('supplier_business_name', 'like', '%' . $term . '%')
                    ->orWhere('mobile', 'like', '%' . $term . '%')
                    ->orWhere('contacts.contact_id', 'like', '%' . $term . '%');
            });
        }

        $contacts = $contacts->select(
            'contacts.id',
            DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')')) AS text"),
            'mobile',
            'landmark',
            'city',
            'state',
            'pay_term_number',
            'pay_term_type'
        )
            ->onlySuppliers()
            ->get()
            ->toArray();
        return $contacts;
    }

    public function getAllProducts(Request $request)
    {
        try {
            $term = request()->get('term');
            $location_id = request()->get('location_id');

            $check_qty = request()->get('check_qty');

            $price_group_id = request()->get('price_group');

            $business_id = request()->get('business_id');

            $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->active()
                ->whereNull('variations.deleted_at')
                ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });;
                        }
                    }
                )
                ->orderBy("name");;
            if (!empty($price_group_id)) {
                $products->leftjoin(
                    'variation_group_prices AS VGP',
                    function ($join) use ($price_group_id) {
                        $join->on('variations.id', '=', 'VGP.variation_id')
                            ->where('VGP.price_group_id', '=', $price_group_id);
                    }
                );
            }
            $products->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            //Include search
            if (false) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
                });
            }


            //Include check for quantity
            if (false) {
                $products->where('VLD.qty_available', '>', 0);
            }

            $products->select(
                'products.id as product_id',
                'products.name',
                'products.our_price',
                'products.second_name',
                'products.product_description',
                'products.type',
                'products.product_custom_field1',
                'products.product_custom_field2',
                'products.product_custom_field3',
                'products.product_custom_field4',
                // 'products.product_custom_field1_price',
                // 'products.product_custom_field2_price',
                // 'products.product_custom_field3_price',
                // 'products.product_custom_field4_price',
                'products.enable_stock',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.sell_price_inc_tax as selling_price',
                'variations.dpp_inc_tax as purchase_price',
                'variations.sub_sku',
                'U.short_name as unit',
                'U.id as unit_id',
            );
            if (!empty($price_group_id)) {
                $products->addSelect('VGP.price_inc_tax as variation_group_price');
            }
            $result = $products->orderBy('VLD.qty_available', 'desc')
                ->get();

            foreach ($result as $single_result) {
                // $attributes = ProductHasAttributeStock::where('product_id', $single_result->product_id)->get();
                $single_result['attributes'] = 'NA';
                // if (!empty($attributes)) {
                //     $single_result['attributes'] = $attributes;
                // }

                $product_quantity_discount_prices = ProductQuantityDiscountPrice::where('product_id', $single_result->product_id)->get();
                $single_result['product_quantity_discount_prices'] = 'NA';
                if (!empty($product_quantity_discount_prices)) {
                    $single_result['product_quantity_discount_prices'] = $product_quantity_discount_prices;
                }

                $single_result['sub_units'] = 'NA';
                // $single_result['sub_units'] = $this->productUtil->getSubUnitsForUnit($single_result->unit_id, $business_id);
                $single_result['variation_group_prices'] = VariationGroupPrice::where('variation_id', $single_result->variation_id)
                    ->select('id', 'variation_id', 'price_group_id', 'price_inc_tax')->get();
            }

            return json_encode($result);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function getProducts(Request $request)
    {

        $term = request()->get('term');
        $location_id = request()->get('location_id');

        $check_qty = request()->get('check_qty');

        $price_group_id = request()->get('price_group');

        $business_id = request()->get('business_id');

        $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
            ->active()
            ->whereNull('variations.deleted_at')
            ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')
            ->leftjoin(
                'variation_location_details AS VLD',
                function ($join) use ($location_id) {
                    $join->on('variations.id', '=', 'VLD.variation_id');

                    //Include Location
                    if (!empty($location_id)) {
                        $join->where(function ($query) use ($location_id) {
                            $query->where('VLD.location_id', '=', $location_id);
                            //Check null to show products even if no quantity is available in a location.
                            //TODO: Maybe add a settings to show product not available at a location or not.
                            $query->orWhereNull('VLD.location_id');
                        });;
                    }
                }
            );
        if (!empty($price_group_id)) {
            $products->leftjoin(
                'variation_group_prices AS VGP',
                function ($join) use ($price_group_id) {
                    $join->on('variations.id', '=', 'VGP.variation_id')
                        ->where('VGP.price_group_id', '=', $price_group_id);
                }
            );
        }
        $products->where('products.business_id', $business_id)
            ->where('products.type', '!=', 'modifier');

        //Include search
        if (!empty($term)) {
            $products->where(function ($query) use ($term) {
                $query->where('products.name', 'like', '%' . $term . '%');
                $query->orWhere('sku', 'like', '%' . $term . '%');
                $query->orWhere('sub_sku', 'like', '%' . $term . '%');
            });
        }

        //Include check for quantity
        if ($check_qty) {
            $products->where('VLD.qty_available', '>', 0);
        }

        $products->select(
            'products.id as product_id',
            'products.name',
            'products.type',
            'products.enable_stock',
            'variations.id as variation_id',
            'variations.name as variation',
            'VLD.qty_available',
            'variations.sell_price_inc_tax as selling_price',
            'variations.dpp_inc_tax as purchase_price',
            'variations.sub_sku',
            'U.short_name as unit',
            'U.id as unit_id'
        );
        if (!empty($price_group_id)) {
            $products->addSelect('VGP.price_inc_tax as variation_group_price');
        }
        $result = $products->orderBy('VLD.qty_available', 'desc')
            ->get();
        return json_encode($result);
    }

    //    STORE POS PRODUCT
    public function storeProduct(Request $request)
    {
        try {
            $business_id = $request->get('business_id');
            $form_fields = ['name', 'second_name', 'brand_id', 'unit_id', 'category_id', 'tax', 'type', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_custom_field1_price', 'product_custom_field2_price', 'product_custom_field3_price', 'product_custom_field4_price', 'product_description', 'our_price'];

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (!empty($module_form_fields)) {
                $form_fields = array_merge($form_fields, $module_form_fields);
            }

            $product_details = $request->only($form_fields);
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->get('user_id');

            $product_details['enable_stock'] = (!empty($request->input('enable_stock')) && $request->input('enable_stock') == 1) ? 1 : 0;
            $product_details['alert_quantity'] = !empty($product_details['alert_quantity']) ? $product_details['alert_quantity'] : 0;

            if (!empty($request->input('sub_category_id'))) {
                $product_details['sub_category_id'] = $request->input('sub_category_id');
            }

            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            DB::beginTransaction();

            $product = Product::create($product_details);

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id, $business_id);
                $product->sku = $sku;
                $product->save();
            }

            if ($product->type == 'single') {
                $this->productUtil->createSingleProductVariation($product->id, $product->sku, $request->input('single_dpp'), $request->input('single_dpp_inc_tax'), $request->input('profit_percent'), $request->input('single_dsp'), $request->input('single_dsp_inc_tax'));
            } elseif ($product->type == 'variable') {
                if (!empty($request->input('product_variation'))) {
                    $input_variations = $request->input('product_variation');
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations);
                }
            }

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $product = "ERROR";
        }
        return $product;
    }

    //STORE POS DATA
    public function storePos(Request $request)
    {

        $is_direct_sale = true;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        try {
            $input = $request->all();

            $input['is_quotation'] = 0;
            
            if (!empty($input['products'])) {
                $business_id = $request->get('business_id');


                $user_id = DB::table('users')->where('username', $request->get('username'))->first()->id;

                $commsn_agnt_setting = $request->get('sales_cmsn_agnt');

                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);


                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = \Carbon::now();
                } else {
                    // $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                    $input['transaction_date'] = $request->input('transaction_date');
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                // if ($commsn_agnt_setting == 'logged_in_user') {
                //     $input['commission_agent'] = $user_id;
                // }


                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }


                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;


                //set selling price group id
                if ($request->has('price_group')) {

                    $input['selling_price_group_id'] = $request->input('price_group');
                }


                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Generate reference number
                if (!empty($input['is_recurring'])) {

                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }


                //add sale agent
                $input['created_by'] = $request->get('created_by');

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);


                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);


                if (!$is_direct_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    $input['payment'][] = $change_return;
                }


                if (!$transaction->is_suspend && !empty($input['payment'])) {
                    $this->transactionUtil->createOrUpdatePaymentLinesAPI($transaction, $input['payment'], $business_id, $user_id);
                }

                $update_transaction = false;
                if (false) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $update_transaction = true;
                }
                if (false) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $update_transaction = true;
                }
                if ($update_transaction) {
                    $transaction->save();
                }

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['products'] as $product) {
                        if ($product['enable_stock']) {
                            $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                            if (!empty($product['base_unit_multiplier'])) {
                                $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                            }
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty
                            );
                        }
                    }


                    //Add payments to Cash Register
                    if (!empty($input['payment'])) {

                        $this->cashRegisterUtil->addSellPaymentsAPI($transaction, $input['payment'], $user_id);
                    }

                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    if (!empty($input['enable_rp']) && $input['enable_rp'] == 1) {
                        $redeemed = !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

                    // customer coin points
                    if (!empty($input['enable_coin_points']) && $input['enable_coin_points'] == 1) {
                        $coin_points_redeemed_amount = !empty($input['coin_points_redeemed_amount']) ? $input['coin_points_redeemed_amount'] : 0;
                        $this->transactionUtil->updateCustomerCoinPoints($contact_id, $transaction->coin_points_earned_amount, 0, $coin_points_redeemed_amount);
                    }

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);


                    $business = [
                        'id' => $business_id,
                        'accounting_method' => "fifo",
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings
                    ];

                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                    //Auto send notification
                    $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                }


                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                DB::commit();


                $output = "DONE#" . $transaction->invoice_no;
            } else {
                $output = "ERROR";
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $error = "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
            dd($error);

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = $e;
            return $output;
        }

        if (!$is_direct_sale) {
            return $output;
        } else {

            return $output;
        }
    }


    public function storeContact(Request $request)
    {


        try {
            $business_id = $request->get('business_id');


            $input = $request->only([
                'type', 'supplier_business_name',
                'name', 'tax_number', 'pay_term_number', 'pay_term_type', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country', 'landmark', 'customer_group_id', 'contact_id', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'email'
            ]);


            $input['business_id'] = $business_id;
            $input['created_by'] = DB::table('users')->where('username', $request->get('username'))->first()->id;

            $input['credit_limit'] = $request->input('credit_limit') != '' ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;


            //Check Contact id
            $count = 0;
            if (!empty($input['contact_id'])) {
                $count = Contact::where('business_id', $input['business_id'])
                    ->where('contact_id', $input['contact_id'])
                    ->count();
            }

            if ($count == 0) {

                //Update reference count
                $ref_count = $this->commonUtil->setAndGetReferenceCount('contacts', $business_id);

                if (empty($input['contact_id'])) {
                    //Generate reference number
                    $input['contact_id'] = $this->commonUtil->generateReferenceNumberAPI('contacts', $ref_count, $business_id);
                }


                $contact = Contact::create($input);

                //Add opening balance
                if (!empty($request->input('opening_balance'))) {
                    $this->transactionUtil->createOpeningBalanceTransaction($business_id, $contact->id, $request->input('opening_balance'));
                }

                $output = $contact;
            } else {

                return "ERROR";
            }
        } catch (\Exception $e) {

            return "ERROR";
        }

        return $output;
    }

    public function getUsers()
    {

        $users = User::all();

        return $users;
    }

    public function postCloseRegister(Request $request)
    {
        try {

            $input = $request->only([
                'closing_amount', 'total_card_slips', 'total_cheques',
                'closing_note'
            ]);
            $input['closing_amount'] = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $user_id = $request->get('user_id');
            $input['closed_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 'close';

            CashRegister::where('user_id', $user_id)
                ->where('status', 'open')
                ->update($input);
        } catch (\Exception $e) {
            return "ERROR";
        }

        return "OK";
    }

    public function getUnits($business_id)
    {
        $unit = Unit::where('business_id', $business_id)
            ->with(['base_unit'])
            ->select([
                'actual_name', 'short_name', 'allow_decimal', 'id',
                'base_unit_id', 'base_unit_multiplier'
            ])->get();

        return json_encode($unit);
    }

    //save purchase

    public function store(Request $request)
    {

        try {
            $business_id = $request->get('business_id');

            $transaction_data = $request->only(['ref_no', 'invoice_no', 'status', 'contact_id', 'transaction_date', 'total_before_tax', 'location_id', 'discount_type', 'discount_amount', 'tax_id', 'tax_amount', 'shipping_details', 'shipping_charges', 'final_total', 'additional_notes', 'exchange_rate', 'return_total']);

            $exchange_rate = $transaction_data['exchange_rate'];

            $user_id = $request->get('user_id');
            $enable_product_editing = $request->get('enable_product_editing');

            //Update business exchange rate.
            Business::update_business($business_id, ['p_exchange_rate' => ($transaction_data['exchange_rate'])]);

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

            //unformat input values
            $transaction_data['total_before_tax'] = $this->productUtil->num_uf($transaction_data['total_before_tax'], $currency_details) * $exchange_rate;

            // If discount type is fixed them multiply by exchange rate, else don't
            if ($transaction_data['discount_type'] == 'fixed') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details) * $exchange_rate;
            } elseif ($transaction_data['discount_type'] == 'percentage') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details);
            } else {
                $transaction_data['discount_amount'] = 0;
            }

            $transaction_data['tax_amount'] = $this->productUtil->num_uf($transaction_data['tax_amount'], $currency_details) * $exchange_rate;
            $transaction_data['shipping_charges'] = $this->productUtil->num_uf($transaction_data['shipping_charges'], $currency_details) * $exchange_rate;
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total'], $currency_details) * $exchange_rate;

            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'purchase';
            $transaction_data['payment_status'] = 'due';
            // $transaction_data['transaction_date'] = $this->productUtil->uf_dateAPI($transaction_data['transaction_date'], true, $business_id);
            $transaction_data['transaction_date'] = \Carbon::now();

            //upload document
            $transaction_data['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            DB::beginTransaction();

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type'], $business_id);
            //Generate reference number
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumberAPI($transaction_data['type'], $ref_count, $business_id);
            }

            $transaction = Transaction::create($transaction_data);

            $purchase_lines = [];
            $purchases = $request->input('purchases');

            $this->productUtil->createOrUpdatePurchaseLines($transaction, $purchases, $currency_details, $enable_product_editing, 'received', true, $business_id);

            //Add Purchase payments
            $this->transactionUtil->createOrUpdatePaymentLinesAPI($transaction, $request->input('payment'), $business_id, $user_id);

            //update payment status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);


            //Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($transaction);

            DB::commit();

            $output = "SAVED";
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = $e;
        }

        return $output;
    }

    public function getLocations($id)
    {

        $locations = BusinessLocation::where('business_id', $id)->get();
        return $locations;
    }

    public function getTodaySales($business_id, $location_id)
    {

        // return $business_id;
        $today = date("Y-m-d");

        // return $today;

        $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
            ->join(
                'business_locations AS bl',
                'transactions.location_id',
                '=',
                'bl.id'
            )
            ->leftJoin(
                'transactions AS SR',
                'transactions.id',
                '=',
                'SR.return_parent_id'
            )
            ->where('transactions.business_id', $business_id)
            ->where('transactions.location_id', $location_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereDate('transactions.transaction_date', '>=', $today)
            ->whereDate('transactions.transaction_date', '<=', $today)
            ->select(
                'transactions.id',
                'transactions.transaction_date',
                'transactions.is_direct_sale',
                'transactions.invoice_no',
                'transactions.tmp_invoice_no',
                'contacts.name',
                'transactions.payment_status',
                'transactions.final_total',
                'transactions.tax_amount',
                'transactions.discount_amount',
                'transactions.discount_type',
                'transactions.total_before_tax'
            )->get();

        return $sells;
    }

    //    check business reward points enables
    public function getBusinessRewardPointSettings($business_id)
    {
        try {
            $business = Business::where('id', $business_id)
                ->select(
                    'enable_rp',
                    'rp_name',
                    'amount_for_unit_rp',
                    'redeem_amount_per_unit_rp',
                    'min_order_total_for_redeem',
                    'min_redeem_point',
                    'max_redeem_point'
                )
                ->first();
            return $business;
        } catch (\Exception $e) {
        }
    }

    //    get customer reward points
    public function getCustomerRewardPoints($business_id, $id)
    {
        try {
            $reward_points = Contact::where([['id', $id], ['business_id', $business_id]])
                ->select(
                    'balance',
                    'total_rp',
                    'total_rp_used',
                    'total_rp_expired'
                )
                ->first();
            return $reward_points;
        } catch (\Exception $e) {
        }
    }

    // customer coin points
    public function getCustomerCoinPoints($business_id, $contact_id)
    {
        try {
            $coin_points = Contact::where([['id', $contact_id], ['business_id', $business_id]])
                ->select(
                    'total_coins',
                    'total_coins_used'
                )
                ->first();
            return $coin_points;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function getPaymentAccounts($business_id)
    {
        return Account::forDropdown($business_id, false, true);
    }

    public function getCustomerInvoices(Request $request, $id)
    {
        $contact_id = $id;
        $business_id = $request->get('business_id');
        $location_id = $request->get('location_id');
        $product_id = $request->get('product_id');
        $customer_latest_invoices = Transaction::join('transaction_sell_lines', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->where([
                ['transactions.business_id', $business_id], ['transactions.location_id', $location_id],
                ['transactions.contact_id', $contact_id], ['transaction_sell_lines.product_id', $product_id]
            ])
            ->select(
                'transactions.id',
                'transactions.invoice_no',
                'transaction_sell_lines.unit_price_inc_tax',
                'products.name'
            )
            ->groupBy('transactions.id')
            ->orderBy('transactions.id', 'desc')
            ->limit(3)
            ->get();
        return $customer_latest_invoices;
    }

    public function getCustomerGroup($business_id, $customer_id)
    {
        return $this->contactUtil->getCustomerGroup($business_id, $customer_id);
    }

    public function getSellingPriceGroups(Request $request)
    {
        $business_id = $request->get('business_id');
        return SellingPriceGroup::where([['business_id', $business_id], ['is_active', true]])->select('id', 'name')->get();
    }
}
