<?php

use Illuminate\Http\Request;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['namespace' => 'Api'], function () {

    Route::group(['namespace' => 'v1'], function () {

        
        //ALOK ==>> START
        Route::get('auth/test', 'AuthController@test');
        Route::get('stripe/test', 'StripeUserDetailsController@test');
        Route::get('cancel/test', 'BookingController@testTime');
        
        //ALOK ==>> END
        
        Route::post('auth/signup', 'AuthController@signup');
        Route::post('auth/socailLogin', 'AuthController@socailLogin');
        Route::post('auth/login', 'AuthController@login');
        Route::post('auth/forgotPassword', 'AuthController@forgotPassword');
        Route::get('auth/confirmMail', 'AuthController@confirmMail');
        Route::post('booking/checkSuperCleaner', 'BookingController@checkSuperCleaner');
        Route::post('auth/checkUserExistsWithSocial', 'AuthController@checkUserExistsWithSocial');
        Route::post('booking/createFrequencyBooking', 'BookingController@createFrequencyBooking');

        Route::middleware('APIToken')->group(function () {
            Route::get('logout', 'AuthController@logout');
            Route::post('auth/changePassword', 'AuthController@changePassword');
            Route::post('auth/editProfile', 'AuthController@editProfile');
            Route::get('auth/getProfile', 'AuthController@getProfile');
            Route::get('auth/logout', 'AuthController@logout');
            Route::post('space/saveMySpace', 'SpaceController@saveMySpace');
            Route::post('space/getMySpaces', 'SpaceController@getMySpaces');
            Route::post('space/editMySpace', 'SpaceController@editMySpace');
            Route::post('booking/add_bookings', 'BookingController@addBookings');
            Route::post('booking/addCleanerToFavourite', 'BookingController@addCleanerToFavourite');
            Route::post('booking/addInstantBookings', 'BookingController@addInstantBookings');
            Route::post('booking/addadvanceBookings', 'BookingController@addadvanceBookings');
            Route::post('booking/addRatingsandReview', 'BookingController@addRatingsandReview');
            Route::post('booking/getAllExtraServices', 'BookingController@getAllExtraServices');
            Route::post('booking/createAdvancedBookings', 'BookingController@createAdvancedBookings');

            // not using any where need to clean this code
            Route::post('booking/cancelBookingsRequest', 'BookingController@cancelBookingsRequest');


            Route::post('booking/cancelAdvancedBookings', 'BookingController@cancelAdvancedBookings');
            Route::post('booking/cancelInstantBookings', 'BookingController@cancelInstantBookings');
            Route::post('booking/cancelInstantBookingFromHomeowner', 'BookingController@cancelInstantBookingFromHomeowner');
            Route::post('booking/cancelAdvancedBookingFromHomeowner', 'BookingController@cancelAdvancedBookingFromHomeowner');
            Route::post('booking/cancelFavouriteCleaner', 'BookingController@cancelFavouriteCleaner');
            Route::post('booking/getBasicServiceTime', 'BookingController@getBasicServiceTime');
            Route::post('payment/paymenthere', 'PaymentController@paymenthere');
            //getAdvancedBookingsValues change to getAllBookingRequests
            Route::post('booking/getAllBookingRequests', 'BookingController@getAllBookingRequests');
            Route::post('booking/confirmBooking', 'BookingController@confirmBooking');
            Route::post('booking/confirmAdvancedBooking', 'BookingController@confirmAdvancedBooking');
            Route::post('booking/getCleanersBookings', 'BookingController@getCleanersBookings');
            //rejectAdvancedCleaning change to rejectBookingRequest
            Route::post('booking/rejectBookingRequest', 'BookingController@rejectBookingRequest');
            Route::post('booking/getBookingDetails', 'BookingController@getBookingDetails');
            Route::post('booking/getDashboardDetails', 'BookingController@getDashboardDetails');
            Route::post('booking/getBookedDates', 'BookingController@getBookedDates');
            Route::post('booking/transferMoney', 'BookingController@transferMoneyToCleaners');
            Route::post('auth/authenticateGovernmentIdCertificate', 'AuthController@authenticateGovernmentIdCertificate');
            Route::post('auth/authenticateSelfie', 'AuthController@authenticateSelfie');
            Route::post('auth/getAuthenticateCertificate', 'AuthController@getAuthenticateCertificate');
            Route::post('tickets/createTicket', 'TicketingController@createTicket');
            Route::post('auth/verifyPhoneNumber', 'AuthController@verifyPhoneNumber');
            Route::post('booking/getCleanerStats', 'BookingController@getCleanerStats');
            Route::post('booking/notificationRead', 'BookingController@notificationRead');
            Route::post('booking/checkBusyBookingFromCalendar', 'BookingController@checkBusyBookingFromCalendar');

            #not using
            Route::post('booking/getCleanersDashboardDetails', 'BookingController@getCleanersDashboardDetails');
            
            Route::post('booking/updateCurrentPositions', 'BookingController@updateCurrentPositions');
            Route::post('booking/getCurrentPositions', 'BookingController@getCurrentPositions');
            Route::post('auth/updateQuickBlockId', 'AuthController@updateQuickBlockId');
            Route::post('booking/getAreaOfWork', 'BookingController@getAreaOfWork');
            Route::post('auth/changePushNotification', 'AuthController@changePushNotification');
            Route::post('auth/changeCleanerWorkStatus', 'AuthController@changeCleanerWorkStatus');
            Route::post('booking/getCleanerSchedule', 'BookingController@getCleanerSchedule');
            Route::post('auth/saveWorkAreaRegion', 'AuthController@saveWorkAreaRegion');
            Route::post('auth/userSavedRegion', 'AuthController@userSavedRegion');
            Route::post('booking/getCleanerMonthEarnings', 'BookingController@getCleanerMonthEarnings');
            Route::post('booking/markAsComplete', 'BookingController@markAsComplete');
            Route::post('booking/markAsStart', 'BookingController@markAsStart');
            Route::post('booking/checkIf', 'BookingController@checkIf');

            // stripe/generateCustomerId this api is not calling from front-end. stripe id creating at the time of registration
            Route::get('stripe/generateCustomerId', 'StripeUserDetailsController@generateStripeCustomerId');
            
            Route::post('stripe/saveCreditCardToken', 'StripeUserDetailsController@saveStripeCCToken');
            Route::post('stripe/deleteCard', 'StripeUserDetailsController@deleteCardOfCustomer');
            Route::post('stripe/setDefaultCard', 'StripeUserDetailsController@setDefaultCardForCustomer');
            Route::get('stripe/getAllCards', 'StripeUserDetailsController@getAllCardDetails');
            Route::post('stripe/getSingleCard', 'StripeUserDetailsController@getSingleCardDetails');
            Route::post('stripe/updateCardDetails', 'StripeUserDetailsController@updateCreditCardDetails');
            Route::get('stripe/generateRecipient', 'StripeUserDetailsController@generateStripeRecipient');
            Route::post('stripe/saveBankToken', 'StripeUserDetailsController@saveBankActToken');
            Route::post('stripe/getBankDetails', 'StripeUserDetailsController@getBankAccountDetails');
            Route::get('stripe/acceptTerms', 'StripeUserDetailsController@acceptTermsOfServices');
            Route::get('stripe/addIdentityBankAct', 'StripeUserDetailsController@attachPersonWithAccount');
            Route::get('stripe/getAllBankDetails', 'StripeUserDetailsController@getAllBankAccountDetails');
            Route::get('stripe/checkIdentityStatus', 'StripeUserDetailsController@checkIdentityStatus');
            Route::post('booking/penaltyHomeOwner', 'BookingController@deductHomeOwnerForCancellation');

            
            Route::post('booking/deductRatingCleaners', 'BookingController@deductRatingCleaners');


            Route::post('auth/sendPhoneOtp', 'AuthController@sendPhoneOtp');
            Route::post('auth/validatePhoneVerification', 'AuthController@validatePhoneVerification');

        });
    });
});
