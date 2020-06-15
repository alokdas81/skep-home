<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\api\v1\Token;
use App\Models\api\v1\Users;
use App\Models\api\v1\Bookings;
use App\Models\admin\Waiting;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Cartalyst\Stripe\Customer;
use Config;
use Illuminate\Http\Request;
use Illumintae\Http\SimpleMessage;
use Illuminate\Support\Facades\Hash;
use Input;
use Auth;
use Mail;
use File;
use DB;
use DateTime;

class PaymentController extends Controller
{

  /**
   * __construct
   *
   * @return \Illuminate\View\View
   */
  public function __construct(Request $request) {      

  }

  public function paymenthere(Request $request){
  	$stripe = Stripe::make(env('sk_test_BSslafBAKGCIDeWsqNBpAc1M00oTENWJA0'));

        $payment = $stripe->Charges()->create([
          "amount"      => 20,
          "currency"    => env('STRIPE_PAYMENT_CURRENCY'),
        ]);

        echo $payment;die;
  } 
}