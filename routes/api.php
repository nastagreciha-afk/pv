<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;

Route::apiResource('invoices', InvoiceController::class)->only([
    'index',
    'show',
    'store',
    'update',
]);
