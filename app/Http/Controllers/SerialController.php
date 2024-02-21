<?php

namespace App\Http\Controllers;


use App\Product;
use App\PurchaseLine;
use App\TransactionSellLine;
use App\Utils\SerialUtil;
use App\VariationLocationDetails;
use App\VariationLocationSerial;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Services\DataTable;

class SerialController extends Controller
{

    /**
     * @var SerialUtil
     */
    private $serialUtil;

    public function __construct(SerialUtil $serialUtil)
    {
        $this->serialUtil = $serialUtil;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $variation_location_serials = VariationLocationSerial::leftJoin('products', 'variation_location_serials.product_id', '=', 'products.id')
//            ->join('purchase_lines', 'purchase_lines.product_id', '=', 'products.id')
                ->leftJoin('transactions', 'variation_location_serials.ref_no_transaction_id', '=', 'transactions.id')
                ->leftJoin('transactions as t2', 'variation_location_serials.invoice_no_transaction_id', '=', 't2.id')
//            ->leftJoin('transaction_sell_lines', 'transaction_sell_lines.product_id', '=', 'products.id')
//            ->leftJoin('transactions as t2', 'transaction_sell_lines.transaction_id', '=', 't2.id')
                ->select(
                    'variation_location_serials.id',
                    'variation_location_serials.product_id',
                    'products.name',
                    'variation_location_serials.serial_number',
                    'variation_location_serials.status',
                    'transactions.ref_no',
                    't2.invoice_no',
                    'variation_location_serials.ref_no_transaction_id',
                    'variation_location_serials.invoice_no_transaction_id'
                )
                ->distinct()
                ->groupBy(
                    'variation_location_serials.id'
                )
                ->orderBy('variation_location_serials.id', 'desc')
                ->get();

//            dd($variation_location_serials);

//        filters
            if ($request->get('product_id')) {
                $product_id = $request->get('product_id');
                $variation_location_serials = $variation_location_serials->where('product_id', $product_id);
            }

            if ($request->get('serial_number_id')) {
                $serial_number_id = $request->get('serial_number_id');
                $variation_location_serials = $variation_location_serials->where('id', $serial_number_id);
            }

            if ($request->get('status')) {
                $status = $request->get('status');
                $variation_location_serials = $variation_location_serials->where('status', $status);
            }

            try {
                return DataTables::of($variation_location_serials)
                    ->editColumn('product_name', function ($row) {
                        return $row->name;
                    })
                    ->editColumn('status', function ($row) {
                        if ($row->status == 'available') {
                            $html = '<span class="label label-success">' . $row->status . '</span>';
                        } elseif ($row->status == 'sold') {
                            $html = '<span class="label label-danger">' . $row->status . '</span>';
                        } elseif ($row->status == 'stock_adjusted') {
                            $html = '<span class="label label-primary"> Stock Adjusted </span>';
                        } else {
                            $html = '<span class="label label-warning">' . $row->status . '</span>';
                        }
                        return $html;
                    })
                    ->editColumn('purchase_ref_no_invoice_no', function ($row) {
                        if ($row->ref_no != null && $row->invoice_no != null) {
                            return $row->ref_no . ' / ' . $row->invoice_no;
                        } elseif ($row->ref_no != null && $row->invoice_no == null) {
                            return $row->ref_no;
                        } elseif ($row->ref_no == null && $row->invoice_no != null) {
                            return $row->invoice_no;
                        } else {
                            return '';
                        }
                    })
                    ->rawColumns(['status', 'purchase_ref_no_invoice_no'])
                    ->make(true);

            } catch (\Exception $e) {
            }

        }

        $products = Product::where('is_inactive', false)->select('id', 'name')->get();
        $serial_numbers = VariationLocationSerial::orderBy('id', 'desc')->select('id', 'serial_number')->get();
        return view('serial.index', compact('products', 'serial_numbers'));
    }

    public function create($product_id, $quantity, $row_id)
    {
        return view('serial.create', compact('product_id', 'quantity', 'row_id'));
    }

    public function store(Request $request, $product_id)
    {
        if ($request->ajax()) {
            $variation_location_detail = VariationLocationDetails::where('product_id', $product_id)->first();
            $serial_create['variation_location_detail_id'] = $variation_location_detail->id;
            $serial_create['product_id'] = $product_id;
            $serial_create['serial_number'] = $request->get('serial_number');
            $serial_create['status'] = 'available';
            $status = $this->serialUtil->storeSerialNumber($serial_create);
            return $status;
        }
    }

    public function show($id)
    {
        if (\request()->get('type') == 'purchased') {
            $purchased_serials = PurchaseLine::find($id)->serials;
            $serials = explode(',', $purchased_serials);
        } else {
            $transaction_sell_line_serials = TransactionSellLine::find($id)->sold_serials;
            $serials = explode(',', $transaction_sell_line_serials);
        }
        return view('serial.show', compact('serials'));
    }

    public function destroy($serial_number_id)
    {
        if (\request()->ajax()) {
            $this->serialUtil->deleteSerialNumber($serial_number_id);
            return 'Done';
        }
    }

//    public function tableRefresh($product_id)
//    {
//        if (\request()->ajax()) {
//            return $this->serialUtil->serialTableRefresh($product_id);
//        }
//    }

    public function sellCreate($product_id, $quantity, $row_id)
    {
        return view('serial.sell-create', compact('product_id', 'quantity', 'row_id'));
    }

    public function getSerialNumber($product_id, $serial_number)
    {
        if (\request()->ajax()) {
            $serial = VariationLocationSerial::where([['product_id', $product_id], ['serial_number', $serial_number], ['status', 'available']])->first();
            if (!empty($serial)) {
                return 'Have';
            } else {
                return 'No';
            }
        }
    }

    public function checkSerialNumber($serial_number)
    {
        if (\request()->ajax()) {
            $serial = VariationLocationSerial::where('serial_number', $serial_number)->first();
            if (!empty($serial)) {
                return 'Have';
            } else {
                return 'No';
            }
        }
    }

}