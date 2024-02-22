<?php

use App\Http\Controllers\BarcodeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('{any}', function() {
//return "SUSPENDED";
// })->where('any', '.*');

Route::get('/login', 'APIController@login');
Route::get('/getbusiness/{id}', 'APIController@getbusiness');
Route::get('/getlocation/{id}', 'APIController@getLocations');
Route::get('/loadData', 'APIController@loadData');
Route::get('/products', 'APIController@loadProducts');

Route::get('/chekCashRegister/{user}', 'APIController@OpenedRegister');
Route::get('/chekCashRegister/getid/{user}', 'APIController@OpenedRegisterID');
Route::post('/chekCashRegister/save/expense', 'APIController@saveRegisterExpense');
Route::get('/chekCashRegister/total/expense/{id}', 'APIController@TotalRegisterExpense');
Route::get('/chekCashRegister/view/expense/{id}', 'APIController@ViewRegisterExpense');
Route::get('/chekCashRegister/view/{id}/{user_id}', 'APIController@getCloseRegister');

Route::post('/openRegister', 'APIController@openRegister');
Route::get('/getDefalutCustomer/{business_id}', 'APIController@getWalkInCustomer');
Route::get('/getcustomers', 'APIController@getCustomers');
// Route::get('/getcustomers/all/{business_id}', 'APIController@getAllCustomers');
Route::get('/getcustomers/all', 'APIController@getAllCustomers');

Route::get('/getsuppliers', 'APIController@getSuppliers');
Route::get('/products/list', 'APIController@getProducts');
Route::get('/products/all', 'APIController@getAllProducts');
Route::post('/products/store', 'APIController@storeProduct');
Route::post('/savepos', 'APIController@storePos');
Route::post('/savepurchase', 'APIController@store');
Route::post('/contact/save', 'APIController@storeContact');
Route::get('/getusers', 'APIController@getUsers');
Route::get('/getunits/{id}', 'APIController@getUnits');
Route::get('/today-sales/{business_id}/{location_id}', 'APIController@getTodaySales');
Route::post('/close-register', 'APIController@postCloseRegister');
Route::post('/purchase-return', 'CombinedPurchaseReturnController@saveAPI');

Route::get('/customer/due/{id}', 'NewAPIController@getTotalDue');
//check redeem points enabled
Route::get('get/business/reward-point/settings/{business_id}', 'APIController@getBusinessRewardPointSettings');
//get customer redeem points
Route::get('get/customer/reward-points/{business_id}/{id}', 'APIController@getCustomerRewardPoints');
//get payment accounts
Route::get('get/payment/accounts/{business_id}', 'APIController@getPaymentAccounts');
// get customer group
Route::get('get/customer-group/{business_id}/{customer_id}', 'APIController@getCustomerGroup');
// get customer coin points
Route::get('get/customer/coin-points/{business_id}/{contact_id}', 'APIController@getCustomerCoinPoints');
// get selling price groups
Route::get('get/selling/price/groups', 'APIController@getSellingPriceGroups');

// barcode api routes
Route::get('products', [BarcodeController::class, 'allProducts']);

Route::get('business', [BarcodeController::class, 'getBusiness']);

Route::get('purchase', [BarcodeController::class, 'purchases']);