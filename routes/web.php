<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('login', 'Auth\AuthController@login');
    $router->post('logout', 'Auth\AuthController@logout');

    // $router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('vendors-search', 'EPRS\VendorsController@searchData');
    $router->get('po-search', 'EPRS\PurchaseOrdersController@searchData');

    $router->get('vendor-accounts-search', 'AccApp\VendorAccountsController@searchData');
    $router->get('tax-accounts-get', 'AccApp\TaxAccountsController@getData');

    $router->get('vendors-general-search', 'General\VendorsController@searchData');

    $router->get('rnt-get', 'General\ReceiptNoteTypesController@getData');

    $router->get('apvh-get', 'General\ApprovalHeadersController@getData');
    $router->get('apvh-get/{id}', 'General\ApprovalHeadersController@getDetail');
    $router->get('apvh-status/{id}', 'General\ApprovalHeadersController@status');
    $router->post('apvh-save', 'General\ApprovalHeadersController@saveData');

    $router->get('dep-get', 'General\DepartementsController@getData');

    $router->get('role-get', 'General\RolesController@getData');

    $router->post('do-save', 'General\DeliveryOrdersController@saveData');
    $router->put('do-save/{id}', 'General\DeliveryOrdersController@editData');
    $router->get('do-cancel/{id}', 'General\DeliveryOrdersController@cancelData');
    $router->get('do-get', 'General\DeliveryOrdersController@getData');
    $router->get('do-get/{id}', 'General\DeliveryOrdersController@getDetail');
    $router->get('do-search', 'General\DeliveryOrdersController@searchData');

    $router->post('rn-save', 'General\ReceiptNotesController@saveData');
    $router->get('rn-get', 'General\ReceiptNotesController@getData');
    $router->get('rn-cancel/{id}', 'General\ReceiptNotesController@cancelData');
    $router->get('rn-search', 'General\ReceiptNotesController@searchData');
    $router->get('rn-docx/{id}', 'General\ReceiptNotesController@exportDocx');
    $router->get('rn-get/{id}', 'General\ReceiptNotesController@getDetail');
    $router->get('rn-get-apv/{id}', 'General\ReceiptNotesController@getDataApprove');
    $router->put('rn-save/{id}', 'General\ReceiptNotesController@editData');
    $router->put('rn-save-apv/{id}', 'General\ReceiptNotesController@setApproval');

    $router->post('inv-save', 'General\InvoicesController@saveData');
    $router->get('inv-search', 'General\InvoicesController@searchData');
    $router->get('inv-get', 'General\InvoicesController@getData');
    $router->get('inv-get/{id}', 'General\InvoicesController@getDetail');
    $router->get('inv-cancel/{id}', 'General\InvoicesController@cancelData');
    $router->put('inv-save/{id}', 'General\InvoicesController@editData');

    $router->get('aging-get', 'General\InvoicesController@getAging');
    $router->get('aging-get/{id}', 'General\InvoicesController@getAgingVendor');

    $router->get('aging-pv-get', 'General\PaymentVouchersController@getAging');
    $router->get('aging-pv-get/{id}', 'General\PaymentVouchersController@getAgingVendor');

    $router->get('bank-search', 'AccApp\BankAccountsController@searchData');
    $router->get('bank-get', 'AccApp\BankAccountsController@getData');

    $router->get('pv-get-apv/{id}', 'General\PaymentVouchersController@getDataApprove');
    $router->post('pv-save', 'General\PaymentVouchersController@saveData');
    $router->get('pv-get', 'General\PaymentVouchersController@getData');
    $router->get('pv-search', 'General\PaymentVouchersController@searchData');
    $router->get('pv-docx/{id}', 'General\PaymentVouchersController@exportDocx');
    $router->get('pv-get/{id}', 'General\PaymentVouchersController@getDetail');
    $router->put('pv-save/{id}', 'General\PaymentVouchersController@editData');
    $router->put('pv-save-apv/{id}', 'General\PaymentVouchersController@setApproval');

    $router->post('user-save', 'General\UsersController@saveData');
    $router->get('user-get', 'General\UsersController@getData');
    $router->get('user-search', 'General\UsersController@searchData');
    $router->get('user-get/{id}', 'General\UsersController@getUserDetail');
    $router->get('user-status/{id}', 'General\UsersController@setStatus');
    $router->put('user-save/{id}', 'General\UsersController@editData');

    $router->post('pvr-save/{id}', 'General\PaymentVoucherRealizationsController@saveData');
    $router->get('pvr-get', 'General\PaymentVoucherRealizationsController@getData');
    $router->get('pvr-search', 'General\PaymentVoucherRealizationsController@searchData');
    $router->get('pvr-get/{id}', 'General\PaymentVoucherRealizationsController@getDetail');
    $router->post('pvr-cancel/{id}', 'General\PaymentVoucherRealizationsController@cancelData');

    $router->get('ru-get/{id}', 'General\RoleUsersController@getDataUser');
    // });
});
