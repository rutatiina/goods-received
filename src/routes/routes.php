<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('goods-received')->group(function () {

        Route::post('routes', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@routes')->name('goods-received.routes');
        //Route::get('summary', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@summary');
        Route::post('export-to-excel', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@exportToExcel');
        Route::post('approve', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@approve')->name('goods-received.approve');
        //Route::post('contact-estimates', 'Rutatiina\GoodsReceived\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@copy');
        Route::delete('delete', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@delete')->name('goods-received.delete');
        Route::delete('cancel', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController@cancel')->name('goods-received.cancel');

    });

    Route::resource('goods-received/settings', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedSettingsController');
    Route::resource('goods-received', 'Rutatiina\GoodsReceived\Http\Controllers\GoodsReceivedController');

});
