<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contact;
use DB;

class NewAPIController extends Controller
{

    public function getTotalDue($id)
    {

        $contact = Contact::where('contacts.id', $id)
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->select(
                DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                'contacts.*'
            )->first();

            $due = $contact->total_invoice - $contact->invoice_received;

            return $due;
    }
}
