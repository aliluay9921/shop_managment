<?php

use App\Http\Controllers\DeptRecordController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These 
| routes are loaded by the RouteServiceProvider within a group which  
| is assigned the "api" middleware group. Enjoy building your API !
|
*/

route::post("login", [UserController::class, "login"]);

Route::middleware(["auth:api", "active"])->group(function () {
    route::get("get_goods", [GoodsController::class, "getGoods"]);
    route::get("get_invoices", [InvoiceController::class, "getInvoices"]);

    route::post("add_goods", [GoodsController::class, "addGoods"]);
    route::post("add_invoice", [InvoiceController::class, "addInvoice"]);
    route::post("retrive_goods", [InvoiceController::class, "retriveGoods"]);
    route::post("add_dept_record", [DeptRecordController::class, "addDeptRecord"]);
    route::post("add_goods_to_dept", [DeptRecordController::class, "addGoodsToDept"]);
    route::put("update_goods", [GoodsController::class, "updateGoods"]);

    route::delete("delete_goods", [GoodsController::class, "deleteGoods"]);

    Route::middleware("admin")->group(function () {
        route::get("get_users", [UserController::class, "getUsers"]);
        route::post("add_user", [UserController::class, "addUser"]);
        route::put("toggle_user_active", [UserController::class, "toggleUserActive"]);
    });
});