<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {
    Route::post('send-phone', 'User\Auth\AuthController@sendPhone');
    Route::post('phone-verification', 'User\Auth\AuthController@phoneVerification');
    Route::post('logout', 'User\Auth\AuthController@logout');

    // Route::get('get-profile', 'User\Profile\ProfileController@getProfile');
    Route::get('get-user', 'User\Profile\ProfileController@getUser');
    Route::get('get-discounts', 'User\Profile\ProfileController@getDiscounts');
    Route::get('get-addresses', 'User\Profile\ProfileController@getAddresses');
    Route::post('create-address', 'User\Profile\ProfileController@createAddress');
    Route::post('remove-address', 'User\Profile\ProfileController@removeAddress');
    Route::post('update-user', 'User\Profile\ProfileController@updateUser');
    Route::post('create-avatar', 'User\Profile\ProfileController@createAvatar');
    Route::post('remove-avatar', 'User\Profile\ProfileController@removeAvatar');

    Route::get('get-wallet', 'User\Wallet\WalletController@getWallet');
    Route::get('get-transactions', 'User\Wallet\WalletController@getTransactions');
    Route::post('charge', 'User\Wallet\WalletController@charge');
    // Route::post('clear', 'User\Wallet\WalletController@clear');

    Route::get('get-no-driver-travel', 'User\Request\RequestController@getNoDriverTravel');
    Route::get('get-near-by-drivers', 'User\Request\RequestController@getNearByDrivers');
    Route::post('calculate-price', 'User\Request\RequestController@calculatePrice');
    Route::post('create-travel', 'User\Request\RequestController@createTravel');
    // Route::post('accept-request', 'User\Request\RequestController@acceptRequest');

    Route::get('get-active-travel', 'User\Travel\TravelController@getActiveTravel');
    Route::post('cancel-travel', 'User\Travel\TravelController@cancelTravel');
    // Route::post('rate-travel', 'User\Travel\TravelController@rateTravel');
    Route::post('report-travel', 'User\Travel\TravelController@reportTravel');
    // Route::post('send-location', 'User\Travel\TravelController@sendLocation');

    Route::get('get-travel-records', 'User\History\HistoryController@getTravelRecords');
    Route::get('get-travel-record', 'User\History\HistoryController@getTravelRecord');
});
