<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('goods-received')->group(function () {

        //Route::get('summary', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@summary');
        Route::post('export-to-excel', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@approve');
        //Route::post('contact-estimates', 'Rutatiina\GoodsReceived\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@copy');

    });

    Route::resource('goods-received/settings', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedSettingsController');
    Route::resource('goods-received', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController');

});
