<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use Illumintae\Http\SimpleMessage;
use Illuminate\Support\Facades\Hash;
use App\Models\api\v1\StripeUserDetails;
use App\Models\api\v1\Token;
use App\Models\api\v1\Users;
use Input;
use Auth;
use Mail;
use DB;
use File;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;

use Illuminate\Support\Facades\Route;
//use App\Models\api\v1\Jsons; 
use Illuminate\Support\Facades\Log;

class StripeUserDetailsController extends Controller
{
    /*
    * constructor to get the stripe token
    */
    public function __construct(Request $request){
        
        /*** @auther: ALOK => START ***/
        /*$input_data = $request->input();
        $currentPath= Route::getFacadeRoot()->current()->uri();
        $array = ['action' => $currentPath, 'data' => json_encode($input_data), 'call_type' => 'request'];
        $create_json = Jsons::create($array);
        */
        /*** @auther: ALOK => END ***/
        
        
        
        // Unique Token
        
        $this->stripeKey = Stripe::make(env("STRIPE_SECRET"));
        $this->userType = $request->header('userType') ? $request->header('userType') : "";
        $this->userId = $request->header('userId') ? $request->header('userId') : "";
    }
    function test(Request $request)
    {
        
                      
      
        
        $STRIPE_SECRET = env("STRIPE_SECRET");
        \Stripe\Stripe::setApiKey($STRIPE_SECRET);

        echo '+++++++++++++'.$STRIPE_SECRET;

        $account_id_failed = 'acct_1GgddwEA56eUMKaA'; // failed
        $account_id_success = 'acct_1GaAAuHZoar4DHfE'; // success
        
        echo "<br> success: ".$account_id_success;
        echo "<br> failed: ".$account_id_failed;
        
        $update = \Stripe\Account::updateCapability(
            'acct_1GaAAuHZoar4DHfE',
            'card_payments',
            ['requested' => true]
          );
          echo "<pre>";
          print_r($update);          
          echo "</pre>";

        $capabilities = \Stripe\Account::retrieveCapability(
            'acct_1GaAAuHZoar4DHfE'
            );
        
       /* $account =     \Stripe\Account::retrieve(
                $account_id_failed
              );
        */
        echo "<pre>";
        
        print_r($capabilities);
        echo "</pre>";

        exit;


    }
  /*
    * Function to save generate Home owner's customer account 
    * @Params $token, $user_id
    * TODO send alert to admin if failed to create stripe id            
    */
    public static function generateStripeCustomerId($userId,$signupType ='')
    {    
        
        $userDetails = Users::where('id',$userId)->first();
        if(!empty($userDetails))
        {
            // Creating customer with stripe
            $STRIPE_SECRET = env("STRIPE_SECRET");
            $stripe = Stripe::make($STRIPE_SECRET);

            $name = $userDetails->first_name.' '.$userDetails->last_name; 

            $checkEmailInStripe = self::checkStripeCustomerByEmail($userDetails->email);             
            
            if($checkEmailInStripe)
            {
                $customerId = $response['data'][0]['id']; 
                try{
                   
                    $customer = $stripe->customers()->update(
                        $customerId,[
                        'name' => $name,
                        'phone' =>$userDetails->phone_number,
                        'address' =>[
                            'line1' => $userDetails->address,                
                            ]
                    ]);
                }
                catch(\Exception $e){                           
                    $this->error($e->getMessage());
                }               

            }
            else
            {
                try{
                    $customer = $stripe->customers()->create([
                        'email' => $userDetails->email,
                        'name' => $name,
                        'phone' =>$userDetails->phone_number,
                        'address' =>[
                            'line1' => $userDetails->address,                
                            ]
                    ]);
                }
                catch(\Exception $e){                           
                    $this->error($e->getMessage());
                }
                                                
                $customerId = $customer['id'];                 
            }
                         
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $userId])->first();
            if (empty($stripeUserDetails)) 
            {
                $stripeUserDetails = new StripeUserDetails();
            }
            $stripeUserDetails->customer_id = $customerId;
            $stripeUserDetails->save();
            
            return $stripeUserDetails->customer_id;

        } 
        else
        {                        
            $this->error("User does not exists.");

        }
    }

/*
    * Function to save Home owner's' Credit card token coming through frontend using stripe connect api
    * @Params $token, $user_id
    */
     public function saveStripeCCToken(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "stripeToken"  => "required"
           ]);

        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $token = $request->input('stripeToken');
            if(!empty($token)){
             $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if (!empty($stripeUserDetails)) {
                if(!empty($stripeUserDetails->customer_id)){
            // Updating CC token of customer - stripe call
             $stripe = $this->stripeKey;
             // To generate test token
             /*$token = $stripe->tokens()->create([
                'card' => [
                    'number'    => '371449635398431',
                    'exp_month' => 05,
                    'cvc'       => 123,
                    'exp_year'  => 2023,
                ],
            ]); echo $token['id']; die;*/
            try {
            $card = $stripe->cards()->create($stripeUserDetails->customer_id, $token);
            } catch (Cartalyst\Stripe\Exception\NotFoundException $e) {
                // Get the status code
                $code = $e->getCode();
                // Get the error message returned by Stripe
                $message = $e->getMessage();
                // Get the error type returned by Stripe
                $type = $e->getErrorType();
                $this->error($message,['err'=>5, 'code' => $code, 'type' => $type]);
            }
            if(!empty($card)){
            $cardId = $card['id'];
            $savedTokens = $stripeUserDetails->token;
            if(!empty($savedTokens)){
                $tokensArr = unserialize($savedTokens);
                if(in_array($token, array_column($tokensArr, 'token'))) { // search value in the array
                 $this->error("This token already exists",['err'=>5]); 
                }else{
                 $tokensArr[] = ['token' => $token, 'cardId' => $cardId, 'name' => $card['name'], 'brand' => $card['brand'], 'country' => $card['country'], 'exp_month' => $card['exp_month'], 'exp_year' => $card['exp_year'], 'funding' => $card['funding'], 'last4' => $card['last4'], 'isDefault' => 0];    
                }
            }else{
                $serializedArr['token'] = $token;
                $serializedArr['cardId'] = $cardId;
                $serializedArr['name'] = $card['name'];
                $serializedArr['brand'] = $card['brand'];
                $serializedArr['country'] = $card['country'];
                $serializedArr['exp_month'] = $card['exp_month'];
                $serializedArr['exp_year'] = $card['exp_year'];
                $serializedArr['funding'] = $card['funding'];
                $serializedArr['last4'] = $card['last4'];
                $serializedArr['isDefault'] = 0;

                $tokensArr[] = $serializedArr;
            }
            $stripeUserDetails->token = serialize($tokensArr);
            $stripeUserDetails->save();
            $this->success("Stripe token saved Successfully",['cusId' => $stripeUserDetails->customer_id, 'token' =>$token, 'cardId' => $cardId, 'name' => $card['name'], 'brand' => $card['brand'], 'country' => $card['country'], 'exp_month' => $card['exp_month'], 'exp_year' => $card['exp_year'], 'funding' => $card['funding'], 'last4' => $card['last4'], 'userId' => $this->userId]);
            }else{
            $this->error("Not able to reach stripe",['err'=>4]);    
            }
            }else{
            $this->error("Customer Id does not exist for this user",['err'=>0]);    
            }
            }else{
                $this->error("This user has no records in stripe table",['err'=>1]);
            }
            }else{
            $this->error("Not able to reach stripe",['err'=>2]);    
            }
        } else{
            $this->error("User does not exist",['err'=>3]);
        }
     }

     /*
    * Function to delete Home owner's' Credit card details
    * @Params $token, $user_id
    */
     public function deleteCardOfCustomer(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "cardId"  => "required"
           ]);

        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $cardId = $request->input('cardId');
            if(!empty($cardId)){
             $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if (!empty($stripeUserDetails)) {
                if(!empty($stripeUserDetails->customer_id)){
            // Updating CC token of customer - stripe call
             $stripe = $this->stripeKey;
            try {
            $card = $stripe->cards()->delete($stripeUserDetails->customer_id, $cardId);
            } catch (Cartalyst\Stripe\Exception\ServerErrorException $e) {
                // Get the status code
                $code = $e->getCode();
                // Get the error message returned by Stripe
                $message = $e->getMessage();
                // Get the error type returned by Stripe
                $type = $e->getErrorType();
                $this->error($message,['err'=>6, 'code' => $code, 'type' => $type]);
            }
            if(!empty($card)){
            $savedTokens = $stripeUserDetails->token;
            if(!empty($savedTokens)){
                $tokensArr = unserialize($savedTokens);
                if(!empty($tokensArr)){
                    foreach($tokensArr as $key=>$tokens){
                        if($tokens['cardId'] == $cardId){
                            unset($tokensArr[$key]);
                        }
                    }
                }
            }else{
                $this->error("There is no credit card with this id",['err'=>5]);
            }
            $stripeUserDetails->token = serialize(array_values($tokensArr));
            $stripeUserDetails->save();
            $this->success("Credit card details deleted Successfully",['cusId' => $stripeUserDetails->customer_id, 'cardId' => $cardId, 'userId' => $this->userId]);
            }else{
            $this->error("Not able to reach stripe",['err'=>4]);    
            }
            }else{
            $this->error("Customer Id does not exist for this user",['err'=>0]);    
            }
            }else{
                $this->error("This user has no records in stripe table",['err'=>1]);
            }
            }else{
            $this->error("Not able to reach stripe",['err'=>2]);    
            }
        } else{
            $this->error("User does not exist",['err'=>3]);
        }
     }

     /*
    * Function to delete Home owner's' Credit card details
    * @Params $token, $user_id
    */
     public function setDefaultCardForCustomer(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "cardId"  => "required"
           ]);

        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $cardId = $request->input('cardId');
            if(!empty($cardId)){
             $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if (!empty($stripeUserDetails)) {
                if(!empty($stripeUserDetails->customer_id)){
            // Updating CC token of customer - stripe call
             $stripe = $this->stripeKey;
            try {
            $customer = $stripe->customers()->update($stripeUserDetails->customer_id, [
                'default_source' => $cardId,
            ]); 
            } catch (Cartalyst\Stripe\Exception\ServerErrorException $e) {
                // Get the status code
                $code = $e->getCode();
                // Get the error message returned by Stripe
                $message = $e->getMessage();
                // Get the error type returned by Stripe
                $type = $e->getErrorType();
                $this->error($message,['err'=>6, 'code' => $code, 'type' => $type]);
            }
            if(!empty($customer)){
            $savedTokens = $stripeUserDetails->token;
            if(!empty($savedTokens)){
                $tokensArr = unserialize($savedTokens);
                if(!empty($tokensArr)){
                    foreach($tokensArr as $key=>$tokens){
                        if($tokens['cardId'] == $cardId){
                        $tokensArr[$key]['isDefault'] = 1;
                        }else{
                        $tokensArr[$key]['isDefault'] = 0;  
                        }
                    }
                }
            }else{
                $this->error("There is no credit card with this id",['err'=>5]);
            }
            $stripeUserDetails->token = serialize(array_values($tokensArr));
            $stripeUserDetails->save();
            $this->success("Default Credit card saved Successfully",['cusId' => $stripeUserDetails->customer_id, 'cardId' => $cardId, 'userId' => $this->userId]);
            }else{
            $this->error("Not able to reach stripe",['err'=>4]);    
            }
            }else{
            $this->error("Customer Id does not exist for this user",['err'=>0]);    
            }
            }else{
                $this->error("This user has no records in stripe table",['err'=>1]);
            }
            }else{
            $this->error("Not able to reach stripe",['err'=>2]);    
            }
        } else{
            $this->error("User does not exist",['err'=>3]);
        }
     }

     //============>
     public static function generateStripeRecipientFromBackendSignup($userId){

        $userDetails = Users::where('id',$userId)->first();
        if(!empty($userDetails)){

            $STRIPE_SECRET = env("STRIPE_SECRET");

            $stripe = Stripe::make($STRIPE_SECRET);
         
            $create_stripe_account = false;
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $userId])->first();
            
            if (empty($stripeUserDetails)) {
                
                $create_stripe_account = true;
                $stripeUserDetails = new StripeUserDetails();
            }
            else
            {
                if(empty($stripeUserDetails['account_id']))
                {
                    $create_stripe_account = true;
                }
            }
            $dateOfBirth = $userDetails->date_of_birth;
            $day = date('d',strtotime($dateOfBirth));
            $month = date('m',strtotime($dateOfBirth));
            $year = date('Y',strtotime($dateOfBirth));

            $address = [];
            $address['line1'] = $userDetails->address;

            $country = 'CA';
            if(!empty($userDetails->city))
            {
                $address['city'] = $userDetails->city;
            }
            if(!empty($userDetails->state))
            {
                $address['state'] = $userDetails->state;
            }
            if(!empty($userDetails->country))
            {
                $address['country'] = $userDetails->country;
                $country = $userDetails->country;
            }
            if(!empty($userDetails->postal_code))
            {
                $address['postal_code'] = $userDetails->postal_code;
            }
            $individual =  [
                'first_name' => $userDetails->first_name,
                'last_name' => $userDetails->last_name,
                'phone' => $userDetails->phone_number,
                'email' => $userDetails->email,
                'address' =>$address,
                'dob' => [
                    'day' => $day,
                    'month' => $month,
                    'year' => $year
                ]
                
            ];    
            if($create_stripe_account)
            {                                 
                try 
                {
                    $recipient = $stripe->account()->create([
                        'country' => $country,
                        'type' => 'custom',
                        'email' => $userDetails->email,
                        //'details_submitted' => true,
                        //'payouts_enabled' => true,                                            
                       'business_type' => "individual",                   
                        'individual' => $individual,                
                        'settings' => [
                            'payouts' =>[
                                'schedule' => [
                                    'delay_days' => 7,
                                    'interval' => 'weekly',
                                    'weekly_anchor' => env("WEEKLY_ANCHOR")
                                ]
                            ]
                        ],
                        "requested_capabilities" => ["card_payments", "transfers"]
                    ]); 
                    

                }
                catch(\Exception $e){                           

                    Log::Info("Cleaner Stripe account create FAILED: ". $e->getMessage());
                    return $e->getMessage();

                    //$this->error("Sorry failed to create stripe account. Please contact support at ".env("MAIL_SUPPORT"));
                 }
                                
                if(!empty($recipient)){
                    $recipientId = $recipient['id']; 
                    $personId = $recipient['individual']['id'];                    
                   
                    $stripeUserDetails->account_id = $recipientId;
                    $stripeUserDetails->person_id = $personId;                    
                    $stripeUserDetails->user_id = $userDetails->id;
                    $stripeUserDetails->save();
                   //Retunring the reponse to web service with customer id
                   Log::Info("=============\ngenerateStripeRecipient account_id: ".$userDetails->id." :: ".$stripeUserDetails->account_id);
                   //$this->success("Stripe custom account generated successfully!",['accountId' => $stripeUserDetails->account_id]);
                   return true;
                   
                }else{
                    //$this->error("Not able to reach stripe",['err'=>1]);    
                    return false;
                }
            }
            else
            {
                
                \Stripe\Stripe::setApiKey($STRIPE_SECRET);

                try 
                {
                    $recipient = \Stripe\Account::update($stripeUserDetails['account_id'],[
                        //'country' => 'CA', // not able to change
                        // 'type' => 'custom', // not able to change
                        'email' => $userDetails->email,
                        'business_type' => "individual",                   
                        'individual' => $individual,
                        'settings' => [
                            'payouts' =>[
                                'schedule' => [
                                    'delay_days' => 7,
                                    'interval' => 'weekly',
                                    'weekly_anchor' => env("WEEKLY_ANCHOR")
                                ]
                            ]
                        ],
                        "requested_capabilities" => ["card_payments", "transfers"]
                    ]); 
                } 
                catch(\Exception $e){                           
                   //echo $e->getMessage();
                   //$this->error($e->getMessage());
                   Log::Info("Cleaner Stripe account update FAILED: ". $e->getMessage());
                   return $e->getMessage();
                }

                if(!empty($recipient)){
                    $recipientId = $recipient['id']; 
                    $personId = $recipient['individual']['id'];                                       
                    $stripeUserDetails->account_id = $recipientId;
                    $stripeUserDetails->person_id = $personId;                    
                    $stripeUserDetails->save();
                 
                   Log::Info("=============\ngenerateStripeRecipient account_id: ".$userDetails->id." :: ".$stripeUserDetails->account_id);
                 
                   return true;
                   
                }else{
                    return false;
                }
            }
            return true;
            
                        
        } else{
            
            return "User does not exist";
            
        }
     }

     //============>
    /*
    * Function to generate cleaner's recipient account 
    * @Params $user_id

    Need to delete this endpoint after delete call from front-end
    */
    
     public function generateStripeRecipient(Request $request){
        $input = $request->all();
        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){


            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if (empty($stripeUserDetails['account_id'])) {
                
                $stripe = $this->stripeKey;
                $recipient = $stripe->account()->create([
                    'country' => 'CA',
                    'type' => 'custom',
                    'email' => $userDetails->email,
                    //'details_submitted' => true,
                    //'payouts_enabled' => true,
                    'settings' => [
                    'payouts' =>[
                    'schedule' => [
                    'delay_days' => 7,
                    'interval' => 'weekly',
                    'weekly_anchor' => 'friday'
                    ]
                    ]
                    ]
                    //"requested_capabilities" => ["card_payments", "transfers"]
                ]); 
                if(!empty($recipient)){
                $recipientId = $recipient['id']; 
                $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
                if (empty($stripeUserDetails)) {
                    $stripeUserDetails = new StripeUserDetails();
                }
                $stripeUserDetails->account_id = $recipientId;
                //$stripeUserDetails->token = $request->input('stripeToken');
                $stripeUserDetails->user_id = $userDetails->id;
                $stripeUserDetails->save();
                //Retunring the reponse to web service with customer id
                $this->success("Stripe custom account generated successfully!",['accountId' => $stripeUserDetails->account_id]);
                
            }
            else
            {
                $this->success("Stripe custom account generated successfully!",['accountId' => $stripeUserDetails['account_id']]);
            }

            }else{
            $this->error("Not able to reach stripe",['err'=>1]);    
            }
        } else{
            $this->error("User does not exist",['err'=>2]);
        }
     }
 /*
    * Function to get all card details and also the default card 
    * 
    */
     public function getAllCardDetails(Request $request){
        $input = $request->all();
        
        //Log::Info("INPUT: ".json_encode($input));
        
        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
             $stripe = $this->stripeKey;
             //Log::Info("STRIPE: ".$stripe);
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            
            Log::Info("STRIPE USER: ".json_encode($stripeUserDetails));
            
            if($stripeUserDetails)
            {            
                if(!empty($stripeUserDetails->customer_id))
                {
                    $cards = $stripe->cards()->all($stripeUserDetails->customer_id);
                    $customer = $stripe->customers()->find($stripeUserDetails->customer_id);
                    if($customer && $cards){ 
                        $cardData = [];
                        $defaultSource = $customer['default_source'];
                        foreach($cards['data'] as $key=>$ccCard){ 
                            $cardData[$key]['name'] = $ccCard['name'];
                            if($ccCard['id'] == $defaultSource){
                                $cardData[$key]['isDefault'] = 1;
                            }else{
                                $cardData[$key]['isDefault'] = 0;
                            }
                            $cardData[$key]['last4'] = $ccCard['last4'];
                            $cardData[$key]['brand'] = $ccCard['brand'];
                            $cardData[$key]['id'] = $ccCard['id'];
                            $cardData[$key]['exp_year'] = $ccCard['exp_year'];
                            $cardData[$key]['exp_month'] = $ccCard['exp_month'];
                            $cardData[$key]['customer'] = $ccCard['customer'];
                            $cardData[$key]['object'] = $ccCard['object'];
                            
                        }
                    //Retunring the reponse to web service with customer id
                    $this->success("Cards data retrieved successfully!",['cusId' => $stripeUserDetails->customer_id, 'cardData'=> $cardData]);
                    }else{
                    $this->error("There is no card data available",['err'=>1]); 
                    }
                }
                else{
                    $this->error("Not found any customer ID. You need to generate Customer id firstly.",['err'=>3]);
                }
            }
            else{
                $this->error("Not found any customer ID. You need to generate Customer id firstly.",['err'=>3]);
            }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }
 /*
    * Function to get single card details 
    * @Params $cardId
    */
     public function getSingleCardDetails(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "cardId" => "required"
           ]);
        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
             $stripe = $this->stripeKey;
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if($stripeUserDetails){
            $cards = $stripe->cards()->find($stripeUserDetails->customer_id, $request->input('cardId'));
            if($cards){
            //Retunring the reponse to web service with customer id
            $this->success("Cards data retrieved successfully!",['cusId' => $stripeUserDetails->customer_id, 'cardData'=> $cards]);
            }else{
            $this->error("There is no card data available",['err'=>1]); 
            }
        }else{
            $this->error("Not found any customer ID. You need to generate Customer id firstly.",['err'=>3]);
        }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }

 /*
    * Function to update Credit card details coming through frontend using stripe connect api
    * @Params $cardId, $user_id
    */
     public function updateCreditCardDetails(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "cardId"  => "required"
           ]);

        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $cardId = $request->input('cardId');
            if(!empty($cardId)){
             $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if (!empty($stripeUserDetails)) {
                if(!empty($stripeUserDetails->customer_id)){
            // Updating CC token of customer - stripe call
             $stripe = $this->stripeKey;
            try {
            $card = $stripe->cards()->update($stripeUserDetails->customer_id, $cardId, [
                    'name'          => $request->input('cusName'),
                    'exp_month' => $request->input('expMonth'),
                    'exp_year' => $request->input('expYear'),
                ]);
            } catch (Cartalyst\Stripe\Exception\NotFoundException $e) {
                // Get the status code
                $code = $e->getCode();
                // Get the error message returned by Stripe
                $message = $e->getMessage();
                // Get the error type returned by Stripe
                $type = $e->getErrorType();
                $this->error($message,['err'=>5, 'code' => $code, 'type' => $type]);
            }
            if(!empty($card)){
            $cardId = $card['id'];
            $savedTokens = $stripeUserDetails->token;
            if(!empty($savedTokens)){
                $tokensArr = unserialize($savedTokens); 
                foreach($tokensArr as $key=>$tokens){  
                    if($tokens['cardId'] == $cardId){ 
                 $tokensArr[$key]['exp_month'] = $card['exp_month'];
                 $tokensArr[$key]['exp_year'] = $card['exp_year'];
                 $tokensArr[$key]['name'] = $card['name'];
                }
                }
            }else{
                $this->error("Card is not there! Kindly try to register card again",['err'=>6]);
            }
            $stripeUserDetails->token = serialize($tokensArr);
            $stripeUserDetails->save();
            $this->success("Credit Card details updated Successfully",['cusId' => $stripeUserDetails->customer_id, 'cardId' => $cardId, 'name' => $card['name'], 'brand' => $card['brand'], 'country' => $card['country'], 'exp_month' => $card['exp_month'], 'exp_year' => $card['exp_year'], 'funding' => $card['funding'], 'last4' => $card['last4'], 'userId' => $this->userId]);
            }else{
            $this->error("Not able to reach stripe",['err'=>4]);    
            }
            }else{
            $this->error("Customer Id does not exist for this user",['err'=>0]);    
            }
            }else{
                $this->error("This user has no records in stripe table",['err'=>1]);
            }
            }else{
            $this->error("Not able to reach stripe",['err'=>2]);    
            }
        } else{
            $this->error("User does not exist",['err'=>3]);
        }
     }

    /*
    * Function to save Home owner's' Credit card token coming through frontend using stripe connect api
    * @Params $token, $user_id
    */
     public function saveBankActToken(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "bankToken"  => "required"
           ]);

        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $token = $request->input('bankToken');
            if(!empty($token)){
             $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if (!empty($stripeUserDetails)) {
                if(!empty($stripeUserDetails->account_id)){
            // Updating CC token of customer - stripe call
                $STRIPE_SECRET = env("STRIPE_SECRET");
                \Stripe\Stripe::setApiKey($STRIPE_SECRET);
            try {
             /*   $act = \Stripe\Token::create([
  'bank_account' => [
    'country' => 'US',
    'currency' => 'usd',
    'account_holder_name' => 'Jenny Rosen',
    'account_holder_type' => 'individual',
    'routing_number' => '110000000',
    'account_number' => '000123456789'
  ]
]); echo "<pre>"; print_r($act->id); die;*/
            $bank_account = \Stripe\Account::createExternalAccount(
                  $stripeUserDetails->account_id,
                  [
                    'external_account' => $token,
                  ]
                ); 
            } catch (Cartalyst\Stripe\Exception\NotFoundException $e) {
                // Get the status code
                $code = $e->getCode();
                // Get the error message returned by Stripe
                $message = $e->getMessage();
                // Get the error type returned by Stripe
                $type = $e->getErrorType();
                $this->error($message,['err'=>5, 'code' => $code, 'type' => $type]);
            }
            if(!empty($bank_account)){
            $bankAccountId = $bank_account['id'];
            $savedTokens = $stripeUserDetails->token;
            if(!empty($savedTokens)){
                $tokensArr = unserialize($savedTokens);
                if(in_array($token, array_column($tokensArr, 'token'))) { // search value in the array
                 $this->error("This token already exists",['err'=>5]); 
                }else{
                 $tokensArr[] = ['token' => $token, 'bankAccountId' => $bankAccountId, 'name' => $bank_account['account_holder_name'], 'type' => $bank_account['account_holder_type'], 'country' => $bank_account['country'], 'bank_name' => $bank_account['bank_name'], 'object' => $bank_account['object'], 'currency' => $bank_account['currency'], 'last4' => $bank_account['last4'], 'isDefault' => 1, 'fingerprint' => $bank_account['fingerprint'],'routing_number' =>  $bank_account['routing_number'] , 'status' =>  $bank_account['status']]; 
                }
            }else{
                $serializedArr['token'] = $token;
                $serializedArr['bankAccountId'] = $bankAccountId;
                $serializedArr['name'] = $bank_account['account_holder_name'];
                $serializedArr['type'] = $bank_account['account_holder_type'];
                $serializedArr['country'] = $bank_account['country'];
                $serializedArr['bank_name'] = $bank_account['bank_name'];
                $serializedArr['object'] = $bank_account['object'];
                $serializedArr['currency'] = $bank_account['currency'];
                $serializedArr['last4'] = $bank_account['last4'];
                $serializedArr['isDefault'] = 1;
                $serializedArr['fingerprint'] = $bank_account['fingerprint'];
                $serializedArr['routing_number'] = $bank_account['routing_number'];
                $serializedArr['status'] = $bank_account['status'];
                $tokensArr[] = $serializedArr;
            }
            $stripeUserDetails->token = serialize($tokensArr);
            $stripeUserDetails->save();
            $this->success("Stripe Bank Account token saved Successfully",['accountId' => $stripeUserDetails->account_id, 'token' => $token, 'bankAccountId' => $bankAccountId, 'name' => $bank_account['account_holder_name'], 'type' => $bank_account['account_holder_type'], 'country' => $bank_account['country'], 'bank_name' => $bank_account['bank_name'], 'object' => $bank_account['object'], 'currency' => $bank_account['currency'], 'last4' => $bank_account['last4'], 'isDefault' => 1, 'fingerprint' => $bank_account['fingerprint'],'routing_number' =>  $bank_account['routing_number'] , 'status' =>  $bank_account['status'], 'userId' => $this->userId]);
            }else{
            $this->error("Not able to reach stripe",['err'=>4]);    
            }
            }else{
            $this->error("Account Id does not exist for this user",['err'=>0]); 
            }
            }else{
                $this->error("This user has no records in stripe table",['err'=>1]);
            }
            }else{
            $this->error("Not able to reach stripe",['err'=>2]);    
            }
        } else{
            $this->error("User does not exist",['err'=>3]);
        }
     }

/*
    * Function to get Bank account details 
    * @Params $cardId
    */
     public function getBankAccountDetails(Request $request){
        $input = $request->all();
        $this->validation($request->all(), [
               "bankAccountId" => "required"
           ]);
        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $STRIPE_SECRET = env("STRIPE_SECRET");
            \Stripe\Stripe::setApiKey($STRIPE_SECRET);

            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if($stripeUserDetails){
            $bankActDetails = \Stripe\Account::retrieveExternalAccount($stripeUserDetails->account_id, $request->input('bankAccountId'));
            if($bankActDetails){
            //Retunring the reponse to web service with customer id
            $this->success("Bank Details data retrieved successfully!",['accountId' => $stripeUserDetails->account_id, 'bankData'=> $bankActDetails]);
            }else{
            $this->error("There is no bank account data available",['err'=>1]); 
            }
        }else{
            $this->error("Not found any account ID. You need to generate Custom account id firstly.",['err'=>3]);
        }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }
/*
    * Function to accept terms of service for bank account
    * @Params $cardId
    */
     public function acceptTermsOfServices(Request $request){
        $input = $request->all();
        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $STRIPE_SECRET = env("STRIPE_SECRET");
            \Stripe\Stripe::setApiKey($STRIPE_SECRET);

            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if($stripeUserDetails){
            $terms = \Stripe\Account::update(
                  $stripeUserDetails->account_id,
                  ['tos_acceptance' => [
                      'date' => time(),
                      'ip' => $_SERVER['REMOTE_ADDR']  // Assumes you're not using a proxy
                    ]]);
            if($terms){
            //Retunring the reponse to web service with customer id
            $this->success("Terms of services accepted successfully!",['accountId' => $stripeUserDetails->account_id, 'termsArr'=> $terms]);
            }else{
            $this->error("There is no response for terms of services",['err'=>1]);  
            }
        }else{
            $this->error("Not found any account ID. You need to generate Custom account id firstly.",['err'=>3]);
        }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }

    /*
    * Function to attach a identity with custom account
    * @Params $cardId
    */
     public function attachPersonWithAccount(Request $request){
        $input = $request->all();

        Log::Info("attachPersonWithAccount INPUT: ".json_encode($input));

        $userDetails = Users::where('id',$this->userId)->first();

        Log::Info("attachPersonWithAccount USER: ".json_encode($userDetails));

        if(!empty($userDetails)){
            $dateOfBirth = $userDetails['date_of_birth'];
            if(empty($dateOfBirth)){
                $this->error("Date Of birth field is mandatory. Please add it in your profile.",['err'=>5]);
            }
            $STRIPE_SECRET = env("STRIPE_SECRET");
            \Stripe\Stripe::setApiKey($STRIPE_SECRET);

            // Checking user in stripe table
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            Log::Info("attachPersonWithAccount stripeUserDetails: ".json_encode($stripeUserDetails));
        

            if($stripeUserDetails){
            // Getting the documents
            $govt_id_doc_front = (!empty($userDetails['government_id_image_front']))?$_SERVER['DOCUMENT_ROOT'].'/public/images/authentication_certificates/'.$userDetails['government_id_image_front']:"";
            $govt_id_doc_back = (!empty($userDetails['government_id_image_back']))?$_SERVER['DOCUMENT_ROOT'].'/public/images/authentication_certificates/'.$userDetails['government_id_image_back']:"";
            // If givt id documents are not empty
            if(!empty($govt_id_doc_front) && !empty($govt_id_doc_back)){
            // Api to upload front document
            $fileFront = \Stripe\FileUpload::create(
                  [
                    'purpose' => 'identity_document',
                    'file' => fopen($govt_id_doc_front, 'r')
                  ],
                      ['stripe_account' => $stripeUserDetails->account_id]
                );
            // Api to upload back document
            $fileBack = \Stripe\FileUpload::create(
                  [
                    'purpose' => 'identity_document',
                    'file' => fopen($govt_id_doc_back, 'r')
                  ],
                  ['stripe_account' => $stripeUserDetails->account_id]
                );
            // if both files uploaded
            if(!empty($fileFront) && !empty($fileBack))
            {
            // getting id of both documents
            $fileIdFront = $fileFront['id'];
            $fileIdBack = $fileBack['id'];

            Log::Info("attachPersonWithAccount fileIdFront: ".$fileIdFront);
            Log::Info("attachPersonWithAccount fileIdBack: ".$fileIdBack);

            $individual =  [                
                'verification' => [
                'document' => [
                'front' => $fileIdFront,
                'back' => $fileIdBack
                ]
                ]
            ];    
            Log::Info("person : ".json_encode($individual));          
            
            $person = $updateAct = \Stripe\Account::update(
                          $stripeUserDetails->account_id,
                          [
                          'business_type' => 'individual',
                          'individual' => $individual
                          ]
                        );

            Log::Info("person : ".json_encode($person));          

            if($person){
            $personId = $person['individual']['id'];
            //$stripeUserDetails->person_id = $personId;
            //$stripeUserDetails->save();
            $personDetails = [];
            $personDetails['id'] = $personId;
            $personDetails['object'] = $person['individual']['object'];
            $personDetails['email'] = $person['individual']['email'];
            $personDetails['first_name'] = $person['individual']['first_name'];
            $personDetails['last_name'] = $person['individual']['last_name'];
            $personDetails['id_number_provided'] = $person['individual']['id_number_provided'];
            $personDetails['verification'] = $person['individual']['verification']['status'];
            $personDetails['phone'] = $person['individual']['phone']; 
            //Retunring the reponse to web service with customer id
            Log::Info("attachPersonWithAccount Identity verification : ".$stripeUserDetails->account_id. '+++'.json_encode($personDetails));
        
            $this->success("Identity verification done successfully!",['accountId' => $stripeUserDetails->account_id, 'personDetails'=> $personDetails]);
            }else{
            $this->error("There is no response for this request",['err'=>1]);   
            }
        }else{
           $this->error("File token error",['err'=>3]); 
        }
    }else{
      $this->error("You need to add govt id documents. Otheriwse, KYC will remain pending.",['err'=>4]);  
    }
        }else{
            $this->error("Not found any account ID. You need to generate Custom account id firstly.",['err'=>3]);
        }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }

     /*
    * Function to update a identity with custom account
    * @Params $cardId
    */
    public static function cleanerUpdateIndividual($account_id,$individual)
    {
        $STRIPE_SECRET = env("STRIPE_SECRET");
        \Stripe\Stripe::setApiKey($STRIPE_SECRET);

        Log::Info("cleanerUpdateIndividual person : ",$individual);          
            
        try{
            $person = \Stripe\Account::update(
                $account_id,
                [
                'business_type' => 'individual',
                'individual' => $individual
                ]
              );
        }
        catch(\Exception $e){                           
            
            return $e->getMessage();
        }  
        
        $personDetails = [];
        $personDetails['id'] = $person['individual']['id'];
        $personDetails['object'] = $person['individual']['object'];
        $personDetails['email'] = $person['individual']['email'];
        $personDetails['first_name'] = $person['individual']['first_name'];
        $personDetails['last_name'] = $person['individual']['last_name'];
        $personDetails['id_number_provided'] = $person['individual']['id_number_provided'];
        $personDetails['verification'] = $person['individual']['verification']['status'];
        $personDetails['phone'] = $person['individual']['phone']; 
    
        return $personDetails;
    
    }

     /*
    * Function to update a home owner customer information
    * @Params $cardId
    */
    public static function homeOwnerUpdateCustomer($customer_id,$data)
    {
        $STRIPE_SECRET = env("STRIPE_SECRET");
        
        $stripe = Stripe::make($STRIPE_SECRET);

        try{
                   
            $customer = $stripe->customers()->update(
                $customer_id,$data);
        }
        catch(\Exception $e){                           
            return $e->getMessage();
            
        }
        
        return true;
    }
/*
    * Function to get all Bank account details 
    * @Params $cardId
    */
     public function getAllBankAccountDetails(Request $request){
        $input = $request->all();
        $userDetails = Users::where('id',$this->userId)->first();

        Log::Info("getAllBankAccountDetails: ".json_encode($userDetails));
        if(!empty($userDetails)){
            $STRIPE_SECRET = env("STRIPE_SECRET");
            \Stripe\Stripe::setApiKey($STRIPE_SECRET);

            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if($stripeUserDetails){
            $bankActDetails = $bank_accounts = \Stripe\Account::allExternalAccounts(
                              $stripeUserDetails->account_id,
                              [
                                'object' => 'bank_account',
                              ]
                            ); 

            //Log::Info("bankActDetails: ".json_encode($bankActDetails));
            
            if($bankActDetails){            
            /*$bankDetails = [];
            foreach($bankActDetails as $bank_account){
                $bankDetails['bankAccountId'] = $bank_account['id'];
                $bankDetails['name'] = $bank_account['account_holder_name'];
                $bankDetails['type'] = $bank_account['account_holder_type'];
                $bankDetails['country'] = $bank_account['country'];
                $bankDetails['bank_name'] = $bank_account['bank_name'];
                $bankDetails['object'] = $bank_account['object'];
                $bankDetails['currency'] = $bank_account['currency'];
                $bankDetails['last4'] = $bank_account['last4'];
                $bankDetails['isDefault'] = $bank_account['default_for_currency'];
                $bankDetails['fingerprint'] = $bank_account['fingerprint'];
                $bankDetails['routing_number'] = $bank_account['routing_number'];
                $bankDetails['status'] = $bank_account['status'];
            }
            */
            //Retunring the reponse to web service with customer id
            $this->success("Bank Details data retrieved successfully!",['accountId' => $stripeUserDetails->account_id, 'bankData'=> $bankActDetails]);
            }else{
            $this->error("There is no bank account data available",['err'=>1]); 
            }
        }else{
            $this->error("Not found any account ID. You need to generate Custom account id firstly.",['err'=>3]);
        }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }

 /*
    * Function to check verification sttaus for custom account - cleaners
    * 
    */
     public function checkIdentityStatus(Request $request){
        $input = $request->all();

        Log::Info("checkIdentityStatus INPUT: ".json_encode($input));

        $userDetails = Users::where('id',$this->userId)->first();
        if(!empty($userDetails)){
            $STRIPE_SECRET = env("STRIPE_SECRET");
            \Stripe\Stripe::setApiKey($STRIPE_SECRET);

            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            if($stripeUserDetails){
                $person = \Stripe\Account::retrievePerson(
                    $stripeUserDetails->account_id,
                    $stripeUserDetails->person_id
                  ); 

            Log::Info("********************************\ncheckIdentityStatus person OUTPUT: ".json_encode($person));
        
            if($person){
            $personId = $person['id'];
            $personDetails = [];
            $personDetails['id'] = $personId;
            $personDetails['object'] = $person['object'];
            $personDetails['email'] = $person['email'];
            $personDetails['first_name'] = $person['first_name'];
            $personDetails['last_name'] = $person['last_name'];
            $personDetails['id_number_provided'] = $person['id_number_provided'];
            $personDetails['verification'] = $person['verification']['status'];
            $personDetails['phone'] = $person['phone'];
            //Retunring the reponse to web service with customer id
            
            Log::Info("checkIdentityStatus OUTPUT: ".$stripeUserDetails->account_id.'+++'.json_encode($personDetails));
        
            $this->success("Identity verification response received successfully!",['accountId' => $stripeUserDetails->account_id, 'personDetails'=> $personDetails]);
            }else{
            $this->error("There is no person attached to custom account",['err'=>1]); 
            }
        }else{
            $this->error("Not found any account ID. You need to generate Custom account id firstly.",['err'=>3]);
        }
        }
        else{
            $this->error("User does not exist",['err'=>2]);
        }
     }

     // function to check if email id already exists in the stripe
     
     public static function checkStripeCustomerByEmail($email)
     {
        $stripe = Stripe::make(env("STRIPE_SECRET"));
        try 
        {
            $response = $stripe->customers()->all(["limit" => 1, "email" => $email]);
            
            if(!empty($response['data']))
            {
                return $response['data'][0]['id'];
            }
        } 
        catch (\Exception $e) 
        {
            return false;
        }

        return false;
     }

}
