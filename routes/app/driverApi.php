<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('driver')->group(function () {
    Route::post('send-phone', 'Driver\Auth\AuthController@sendPhone');
    Route::post('phone-verification', 'Driver\Auth\AuthController@phoneVerification');
    Route::post('logout', 'Driver\Auth\AuthController@logout');

    // Route::get('get-profile', 'Driver\Profile\ProfileController@getProfile');
    Route::get('get-user', 'Driver\Profile\ProfileController@getUser');
    Route::get('get-districts', 'Driver\Profile\ProfileController@getDistricts');
    Route::get('get-languages', 'Driver\Profile\ProfileController@getLanguages');
    Route::get('get-cars', 'Driver\Profile\ProfileController@getCars');
    Route::post('update-user', 'Driver\Profile\ProfileController@updateUser');
    Route::post('create-avatar', 'Driver\Profile\ProfileController@createAvatar');
    Route::post('remove-avatar', 'Driver\Profile\ProfileController@removeAvatar');
    Route::post('create-driver-districts', 'Driver\Profile\ProfileController@createDriverDistricts');
    Route::post('remove-driver-districts', 'Driver\Profile\ProfileController@removeDriverDistricts');
    Route::post('create-driver-languages', 'Driver\Profile\ProfileController@createDriverLanguages');
    Route::post('remove-driver-languages', 'Driver\Profile\ProfileController@removeDriverLanguages');
    Route::post('create-driver-car', 'Driver\Profile\ProfileController@createDriverCar');

    Route::get('get-wallet', 'Driver\Wallet\WalletController@getWallet');
    Route::get('get-transactions', 'Driver\Wallet\WalletController@getTransactions');
    Route::get('get-bank-accounts', 'Driver\Wallet\WalletController@getBankAccounts');
    Route::post('create-bank-account', 'Driver\Wallet\WalletController@createBankAccount');
    Route::post('remove-bank-account', 'Driver\Wallet\WalletController@removeBankAccount');
    Route::post('charge', 'Driver\Wallet\WalletController@charge');
    Route::post('clear', 'Driver\Wallet\WalletController@clear');

    Route::get('get-public-travels', 'Driver\Request\RequestController@getPublicTravels');
    // Route::get('get-private-travels', 'Driver\Request\RequestController@getPrivateTravels');
    Route::post('send-request', 'Driver\Request\RequestController@sendRequest');
    // Route::post('accept-request', 'Driver\Request\RequestController@acceptRequest');

    Route::get('get-active-travel', 'Driver\Travel\TravelController@getActiveTravel');
    // Route::post('start-travel', 'Driver\Travel\TravelController@startTravel');
    Route::post('end-travel', 'Driver\Travel\TravelController@endTravel');
    Route::post('cancel-travel', 'Driver\Travel\TravelController@cancelTravel');
    // Route::post('rate-travel', 'Driver\Travel\TravelController@rateTravel');
    Route::post('report-travel', 'Driver\Travel\TravelController@reportTravel');
    Route::post('send-location', 'Driver\Travel\TravelController@sendLocation');

    Route::get('get-travel-records', 'Driver\History\HistoryController@getTravelRecords');
    Route::get('get-travel-record', 'Driver\History\HistoryController@getTravelRecord');
});
