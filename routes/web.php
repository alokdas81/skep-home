<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('welcome');
});



 
Route::prefix('admin')->group(function() {


	Route::get('/', function () {
		return Redirect::to('/admin/login');
	});
	
	Route::get('/login', 'Auth\AdminLoginController@showLoginForm')->name('admin.login');
	Route::post('/login', 'Auth\AdminLoginController@login')->name('admin.login.submit');
	Route::get('/dashboard', 'AdminController@index')->name('admin.dashboard.index');
	Route::resource('/bookingsinstant', 'Admin\BookingsinstantController');
	Route::resource('/bookingsadvanced', 'Admin\BookingsadvancedController');
	Route::resource('/homeowners', 'Admin\HomeownerController');
	Route::resource('/cleaners', 'Admin\CleanersController');
	Route::resource('/waitingtime', 'Admin\WaitingtimeController');
	Route::resource('/extraservices', 'Admin\ExtraservicesController');
	Route::post('cleaner/suspendCleaner', 'Admin\CleanersController@suspendCleaner');
	Route::post('cleaner/approveCleaner', 'Admin\CleanersController@approveCleaner');
	Route::post('cleaner/approveCleaner', 'Admin\CleanersController@approveCleaner');
	Route::post('tickets/closeTicketStatus', 'Admin\TicketsController@closeTicketStatus');
	Route::post('tickets/openTicketStatus', 'Admin\TicketsController@openTicketStatus');
	Route::post('tickets/sendResponseMail', 'Admin\TicketsController@sendResponseMail');
	Route::resource('/tickets', 'Admin\TicketsController');
	Route::get('areaofwork/index', 'Admin\AreaofworkController@index');
	Route::post('areaofwork/saveMapRegions', 'Admin\AreaofworkController@saveMapRegions');
	Route::post('areaofwork/deleteMapRegion', 'Admin\AreaofworkController@deleteMapRegion');
	Route::get('areaofwork/mapRegions', 'Admin\AreaofworkController@mapRegions');
	Route::resource('termsandconditions', 'Admin\TermsandconditionsController');
	Route::resource('basicservices', 'Admin\BasicservicesController');
	Route::resource('futurejobs', 'Admin\FuturejobsController');
	Route::post('homeowner/suspendHomeowner', 'Admin\HomeownerController@suspendHomeowner');
	Route::post('homeowner/approveHomeowner', 'Admin\HomeownerController@approveHomeowner');
	Route::get('mapsofjobs', 'Admin\MapsofjobsController@index');


});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('booking/transferMoney', 'Api\v1\BookingController@transferMoneyToCleaners');
Route::get('auth/resetPass','HomeController@resetPassword');
Route::post('auth/saveResetPass','HomeController@saveResetPassword');
Route::get('api/terms_and_conditions', 'HomeController@termsConditions');
Route::get('api/privacy_policy', 'HomeController@privacyPolicy');


Route::get('images/users/{user_id}/{slug}', [
	'as'         => 'images.show',
	'uses'       => 'ImagesController@show',
	'middleware' => 'auth',
]);
Route::get('uploads/user-files/{filename}', 'ImagesController@show');