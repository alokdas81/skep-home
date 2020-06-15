<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Validator;
//use App\Models\api\v1\Jsons; 
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

use App\Models\api\v1\Bookings;

use Cartalyst\Stripe\Stripe;
use App\Models\api\v1\UserReferralHistories;
use DateTime;
use DateTimeZone;
use Twilio\Rest\Client;
use App\Models\api\v1\Token;
use DB;
use App\Models\admin\Basicservices;
use App\Models\admin\Extraservices;
use App\Models\admin\Regions;
use App\Models\admin\Waiting;

use App\Models\api\v1\Favourites;
use App\Models\api\v1\Myspace;
use App\Models\api\v1\Notifications;
use App\Models\api\v1\Ratings;
use App\Models\api\v1\StripeUserDetails;
use App\Models\api\v1\Users;


class Controller extends BaseController
{
 
    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;
 
    //for notification
    /**
     * Supported devices to send push Notifications
     * IOS
     * ANDROID
     */
    const DEVICES = ['ios', 'android'];
 
    /**
     * Pass phrase of IOS
     * @var null
     */
    private $passPhrase = null;
 
    /**
     * Headers for android
     * @var array
     */
    private $headers = array();
 
    /**
     * Device Token
     * @var null
     */
    private $deviceToken = null;
 
     
    protected function loggedUser()
    {
        return auth()->user();
    }
    function getStripeKey()
    {
        return env("STRIPE_SECRET");
        
    }
    protected function setResponse($response = [], $status = 200)
    {
        
        header('Content-Type: application/json');
        
        $currentPath = trim(Route::getFacadeRoot()->current()->uri());
        /*** @auther: ALOK => START ***/
       /*
        
        $array = ['action' => $currentPath, 'data' => json_encode($response), 'call_type' => 'response'];
        $create_json = Jsons::create($array);
        

        */
        /*** @auther: ALOK => END ***/
        $excludes[] = array(
                        ''
                        );

        if( $currentPath != 'api/booking/updateCurrentPositions')
        {
            Log::Info("\n\n ++++++++++++++++++\n setResponse API:->".$currentPath);        
            Log::Info("API RESPONSE : ".json_encode($response));
            Log::Info("++++++++++++++++++\n\n");
        }
        echo json_encode($response);
        die;
        //return \Response::json($request);

 
    }
 
    /* if operation successfully performed
     * @param  int $msg  Message t obe displayed
     * @param  array  $data data to return with success message
     * @return callback
     */
 
    protected function success($message = "Success", $responseData = [], $status = 200, $token = null)
    {
        $response = [];
        $response['code'] = $status;
        $response['success'] = true;
 
        if ($token != null) {
            $response['token'] = $token;
            $response['message'] = $message;
            $response['data'] = $responseData;
            return $this->setResponse($response, $status);
 
        } else {
            $response['message'] = $message;
            $response['data'] = $responseData;
            return $this->setResponse($response, $status);
 
        }
    }
 
    /**
     * If operation was'nt performed successfully
     * @param  string $error Error Message
     * @return callback
     */
    public function error($message = "Error occured.", $responseData = null, $status = 400)
    {
     
     
        $response = [];
        $response['code'] = $status;
        $response['success'] = false;
        $response['message'] = $message;
        $response['data'] = $responseData;
        return $this->setResponse($response, $status);
    }
 
    public function validation($request = '', $rules = [], $messages = [])
    {
        
        $validator = Validator::make($request, $rules, $messages);
        if ($validator->fails()) {
 
            $this->error(@$validator->errors()->all()[0]);
        }
    }

    public function send_instant_notification($token = array(), $payload = [],$booking_id = '') {// echo $token;die;
        if (is_array($token)) {
            $result = $this->instant_notification($token,$payload);
            //$insert = Notification::create(['sender_id'=>$sender_id, 'receiver_id'=>$user->id, 'title'=>$payload['title'], 'message'=>$payload['body'] ]);
        } else{
            $result = $this->instant_notification($token,$payload);
        }
        
      //  echo "<pre>";print_r($result);die;
        
        return true;
    }

    public function instant_notification($tokens = '',$payload = [], $booking_id = ''){
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");

        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "InstanceBooking.wav",
            'alert' => 1
        ];
        $notification_data = ['booking_id'=> (string) @$payload['value'], 'type'=> @$payload['type'], 'user_type' => @$payload['user_type'], 'notification_date' => date("Y-m-d H:i:s")];
        $extra_notificationData = ["message" => $notification_data];
        
        $fcm_notification = [
            'registration_ids'        => $tokens, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        $result = json_decode($result,true);
        Log::Info("====================");
        Log::Info("INSTANT PUSH INPUT: ".json_encode($fcm_notification));
        Log::Info("INSTANT PUSH RESULT: ".json_encode($result));
        Log::Info("====================");
        
        curl_close($ch);

        if($result["success"] > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
        
    }

    public function send_orphan_notification($tokens = '',$payload = [], $booking_id = ''){
        
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");

        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,            
            'alert' => 1
        ];
        $notification_data = ['notification_date' => date("Y-m-d H:i:s")];
        $extra_notificationData = ["message" => $notification_data];
        
        $fcm_notification = [
            'registration_ids'        => $tokens, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        $result = json_decode($result,true);
        Log::Info("====================");
        Log::Info("ORPHAN PUSH INPUT: ".json_encode($fcm_notification));
        Log::Info("ORPHAN PUSH RESULT: ".json_encode($result));
        Log::Info("====================");
        
        curl_close($ch);

        if($result["success"] > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
        
    }

    public function send_cleaner_rating_notification($token = '',$payload = []){
      
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");
        
        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];
        $notification_data = ['type'=> @$payload['type'], 'user_type' => @$payload['user_type']];
        $extra_notificationData = ["message" => $notification_data];
        
        $fcm_notification = [
            'to'        => $token, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];

        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        
        Log::Info("====================");
        Log::info('RATING  PUSH : '.json_encode($fcm_notification));
        Log::info('RATING PUSH RESULT: '.json_encode($result));
        Log::Info("====================");
                
        curl_close($ch);
        return $result;
    }



    public function send_notification($token = '', $payload = []) {
        $result = $this->notification($token,$payload);
        return true;
    }

    public function notification($token = '',$payload = []){
        
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");
        
        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];
        $notification_data = ['booking_id'=> (string) @$payload['value'], 'type'=> @$payload['type'], 'user_type' => @$payload['user_type']];
        $extra_notificationData = ["message" => $notification_data];

        $fcm_notification = [
            'to'        => $token, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        
        Log::Info("====================");
        Log::Info("PUSH INPUT: ".json_encode($fcm_notification));
        Log::Info("PUSH RESULT: ".json_encode($result));
        Log::Info("====================");
        
        
        curl_close($ch);
        return true;
    }


    public function send_accept_notification($token = array(), $payload = []) {
        
        return $this->normal_notification($token,$payload);
        
        
    }

    public function send_booking_complete_notification($token = '',$payload = []){

        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");
        
        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];
        $notification_data = ['type'=> @$payload['type'], 'user_type' => @$payload['user_type']];
        $extra_notificationData = ["message" => $notification_data];
        
        $fcm_notification = [
            'to'        => $token, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];

        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        
        Log::Info("====================");
        Log::info('markAsComplete PUSH : '.json_encode($fcm_notification));
        Log::info('markAsComplete  PUSH RESULT: '.json_encode($result));
        Log::Info("====================");
                
        curl_close($ch);
        return $result;
    }

    public function send_booking_in_progress_notification($token = '',$payload = []){

        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");
        
        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];
        $notification_data = ['type'=> @$payload['type'], 'user_type' => @$payload['user_type']];
        $extra_notificationData = ["message" => $notification_data];
        
        $fcm_notification = [
            'to'        => $token, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];

        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        
        Log::Info("====================");
        Log::info('Booking IN Progress PUSH : '.json_encode($fcm_notification));
        Log::info('Booking IN Progress PUSH RESULT: '.json_encode($result));
        Log::Info("====================");
                
        curl_close($ch);
        return $result;
    }
    public function normal_notification($token = '',$payload = []){
        
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");
        
        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];
        $notification_data = ['type'=> @$payload['type'], 'user_type' => @$payload['user_type']];
        $extra_notificationData = ["message" => $notification_data];
        
        $fcm_notification = [
            'to'        => $token, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];

        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        
        Log::Info("====================");
        Log::info('CLEANER ACCEPT PUSH : '.json_encode($fcm_notification));
        Log::info('CLEANER ACCEPT PUSH RESULT: '.json_encode($result));
        Log::Info("====================");
                
        curl_close($ch);
        return $result;
    }

    public function send_advanced_booking_notification($token = array(),$payload = []){
        $result = $this->send_advanced_notification($token,$payload);
        return $result;
    }

    public function send_advanced_notification($token = '', $payload = []){
        
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");

        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'alert' => 1
        ];

        $notification_data = ['type'=> @$payload['type'], 'user_type' => @$payload['user_type'], 'booking_id' => @$payload['value']];
        $extra_notificationData = ["message" => $notification_data];
        $fcm_notification = [
            'registration_ids' => $token, 
            'notification' => $notification,
            'data' => $extra_notificationData
        ];

        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);
        
        Log::Info("====================");
        Log::info('SEND ADVANCE PUSH : '.json_encode($fcm_notification));
        Log::info('SEND ADVANCE PUSH RESULT: '.json_encode($result));
        Log::Info("====================");
        
        curl_close($ch);
        return true;
    }

    public function send_cancel_notification($token = array(), $payload = []) {
        
        $result = $this->cancel_notification($token,$payload);

        return true;
    }

    public function cancel_notification($token = '',$payload = []){
        
        $legacy_key = env("FCM_GOOGLE_API_KEY");        
        $fcm_url = env("FCM_URL");

        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];

        $extra_notificationData = [];
        if(array_key_exists('notification_data',$payload))
        {
            $notification_data = $payload['notification_data'];            

            $extra_notificationData = ["message" => $notification_data];
            
            $fcm_notification = [
                'to'        => $token, 
                'notification' => $notification,
                'data' => $extra_notificationData
            ];
        }
        else
        {
            $fcm_notification = [
                'to'        => $token, 
                'notification' => $notification            
            ];

        }        
        
        
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);

        Log::Info("====================");
        Log::info('SEND CANCEL PUSH : '.json_encode($fcm_notification));
        Log::info('SEND CANCEL PUSH RESULT: '.json_encode($result));
        Log::Info("====================");

        curl_close($ch);
        return true;
    }

    public function pre_service_notification($token = '',$payload = []){
        
        $legacy_key = env("FCM_GOOGLE_API_KEY");
        $fcm_url = env("FCM_URL");
        
        $notification = [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'badge' => 1,
            'sound' => "default",
            'alert' => 1
        ];
        $extra_notificationData = ["message" => $notification];
        
        $fcm_notification = [
            'to'        => $token, 
            'notification' => $notification
        ];
        
        $headers = [
            'Authorization: key='.$legacy_key.' ',
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcm_notification));
        $result = curl_exec($ch);

        Log::Info("====================");
        Log::info('SEND PRE SERVICE PUSH : '.json_encode($fcm_notification));
        Log::info('SEND PRE SERVICE PUSH RESULT: '.json_encode($result));
        Log::Info("====================");

        curl_close($ch);
        return true;
    }
    
    /**
    * @Auther : Alok
    * @param 
    * @var null
    * //this function convert string to UTC time zone
    */
    function userTimeToUTCTime($str, $userTimezone, $format = 'Y-m-d H:i:s'){
        
        $new_str = new DateTime($str, new DateTimeZone(  $userTimezone  ) );
        $new_str->setTimeZone(new DateTimeZone('UTC'));
        return $new_str->format( $format);
    }
    
    /**
    *  Auther : Alok
    * Pass phrase of IOS
    * @var null
    * this function converts string from UTC time zone to current user timezone
    */
    //
    function utcTimeToUserTime($str, $userTimezone, $format = 'Y-m-d H:i:s'){
        if(empty($str)){
            return '';
        }
        Log::Info('===============>userTimezone '.$userTimezone);
        $new_str = new DateTime($str, new DateTimeZone('UTC') );
        $new_str->setTimeZone(new DateTimeZone( $userTimezone ));
        return $new_str->format( $format);
    } 
    
    function setTimeZone($str, $timezone = 'UTC'){
        
        $new_str = new DateTime($str, new DateTimeZone($timezone  ) );
        $new_str->setTimeZone(new DateTimeZone($timezone));
        
    }

    function ratingFormat($number)
    {
        return number_format($number,1,'.',',');
    }

    /* Simple function to get certificate link */
    public function get_authenticate_certificate($image = '', $folder_name = ''){
        if(!empty($image)){
            $image_path = url('/').'/public/images/'.$folder_name.'/'.$image;
        } else{
            $image_path = '';
        }
        return $image_path;
    }
    
    /*
     * Function for refund stripe amount  to home owner 
     * call from home owner or cleaner end
     * refund stripe amount if any 
     * refund referral balance if any
     * after deduct skep percentage
     */
    public function refundHomeOwnerForCancellation($userId,$booking_data)
    {
        Log::Info("refundHomeOwnerForCancellation::: ".json_encode($booking_data));

        $booking_id = $booking_data['id'];
        $charge_id = $booking_data['charge_id'];
        $booking_price = $booking_data['booking_price'];
        $skep_charge_amount = $booking_data['skep_charge_amount'];
        $amount_paid = $booking_data['amount_paid'];
        $referral_discount = $booking_data['referral_discount'];
        $cancelled_by = $booking_data['cancelled_by'];
        
        $homeowner_penalty_percent = $booking_data['homeowner_penalty_percent'];
        
        /* Check user that cancel booking exist or not */
        $check_user_exists = Users::where('id', $userId)->first();
        if (!empty($check_user_exists)) 
        {
            $referral_balance = $check_user_exists['referral_balance'];
            
            $stripe_refund = false;
            $balance_refund = 0;
            $stripe_refund_amount = 0;
            $refund_id = NULL;
            if($cancelled_by == 'cleaner')  // if cleaner cancel booking then 100% refund with skep charge
            {
                if(!empty($charge_id)) // if charge id is there need stripe refund will made
                {
                    $stripe_refund = true;
                    $stripe_refund_amount = $amount_paid;
                    $balance_refund = $referral_discount;

                }
                else    // means total service paid from referral balance
                {
                    $balance_refund = $referral_discount;
                    
                }
            }
            else
            {
                /** if homeowner pay some penalty for late cancel booking */
                if($homeowner_penalty_percent > 0)
                {
                    $total_booking_amount = $booking_price+ $skep_charge_amount;

                    $deduct_amount = $this->amountToFloat($total_booking_amount * $homeowner_penalty_percent / 100);

                    $refund_amount = ($amount_paid + $referral_discount) - $deduct_amount;

                    if(!empty($charge_id)) // if charge id is there need stripe refund will made
                    {
                        if($amount_paid >= $refund_amount)
                        {
                            $stripe_refund = true;
                            $stripe_refund_amount = $this->amountToFloat($refund_amount);
                            
                        }
                        else //15.75
                        {
                            $stripe_refund = true;
                            $stripe_refund_amount = $amount_paid;
                            $balance_refund = $this->amountToFloat($refund_amount - $amount_paid);

                        }
                    }
                    else
                    {
                        $balance_refund = $this->amountToFloat($balance_refund - $refund_amount);
                    }

                }
                else /** if homeowner cancel a booking before penalty period*/
                {
                    if(!empty($charge_id))
                    {
                        $stripe_refund = true;
                        $stripe_refund_amount = $amount_paid;
                        $balance_refund = $referral_discount;

                    }
                    else
                    {
                        $balance_refund = $referral_discount;
                    }
                    
                }
                
            }            
            
            $STRIPE_SECRET = $this->getStripeKey();
            \Stripe\Stripe::setApiKey($STRIPE_SECRET);

            if($stripe_refund_amount > 0)
            {
                $stripe_refund_amount = $this->amountToFloat($stripe_refund_amount);

                $stripe_refund_amount_in_cent = $stripe_refund_amount*100;


                $amount_paid_in_cent = $amount_paid*100;

                $transaction = [];
                if($stripe_refund_amount_in_cent < $amount_paid_in_cent)
                {                    
                    $charge = \Stripe\Charge::retrieve($charge_id,[]);                        
                    $transaction = $charge->capture();                    
                }

                $refund = \Stripe\Refund::create([
                    'charge' => $charge_id,
                    'amount' => $stripe_refund_amount_in_cent,
                ]);
                Log::Info("=================================== ");    
                Log::Info("=================================== ");    
                Log::Info("refund : ".json_encode($transaction));
                Log::Info("refund : ".json_encode($refund));
                Log::Info("=================================== ");    
                Log::Info("=================================== ");    
    
                if (!empty($refund)) 
                {
                    $refund_id =  $refund['id'];
                }
                else 
                {
                    $this->error("Some issue occured with stripe. Not able to deduct at this time.");
                }
            }

            $balance_refund = $this->amountToFloat($balance_refund);
            if($balance_refund > 0)
            {
                $remaining_referral_balance = $this->amountToFloat($referral_balance + $balance_refund);
                $update_data = Users::where(['id' => $userId])->update(['referral_balance' => $remaining_referral_balance]);

            }            

            $total_refund_amount = $this->amountToFloat($stripe_refund_amount + $balance_refund);            
           
            $stripeData = [
                'refund_id' => $refund_id, 
                'cancel_amount' => $total_refund_amount, 
                'booking_price' => $booking_price, 
                'stripe_refund_amount' => $stripe_refund_amount, 
                'balance_refund' => $balance_refund,                 
                'cancelled_by' => $cancelled_by,
                'is_cancelled' => 1,                 
                'is_in_progress' => 0,
                'is_on_route' => 0,
                'advance_fav_cleaner_notify'=>2
            ];
            Log::Info("UPDATE SaveChargesStripeData====>".$booking_id.'=='.json_encode($stripeData));
            $updateStripeData = Bookings::SaveChargesStripeData($stripeData, $booking_id);            
            //$updateStripeData = Bookings::where(['id' => $booking_id])->update($stripeData);

            if ($updateStripeData) 
            {
                $stripeData['refund_amount'] = $total_refund_amount;
                $stripeData['currency'] = env("STRIPE_PAYMENT_CURRENCY");
                //$stripeData['status'] = $refund['status'];
                $stripeData['status'] = 'refunded';

                return $stripeData;

            } 
            else 
            {
                $this->error("Refund data not saved in db.");
            }
                        
        } 
        else 
        {
            $this->error("User does not exist.", ['err' => 0]);
        }
    }

    /*
     * Function used to create charge object
     * Used in confirm booking cases to charge home owner
     */
    public function createStripeCharge($charge_params)
    {
        
        $STRIPE_SECRET = $this->getStripeKey();
        \Stripe\Stripe::setApiKey($STRIPE_SECRET);

        try{

            $charge = \Stripe\Charge::create([
                "amount" => $charge_params['total_in_cent'],
                "currency" => "cad",
                "capture" => false,
                "customer" => $charge_params['cusId'], // obtained with Stripe.js
                "description" => "Charge for " . $charge_params['first_name'] . "-" . $charge_params['email'] . ' job id : ' . $charge_params['job_id'],
            ]);
            
        }
        catch(\Exception $e){

           // throw new Exception('Failed to create charge');
            $this->error($e->getMessage());
        }
        
        return $charge;
    }
    
    
    /*
     * Function used to create charge object
     * Used in confirm booking cases to charge home owner
     */
    public function captureStripeCharge($charge_params)
    {
        $STRIPE_SECRET = env("STRIPE_SECRET");
        \Stripe\Stripe::setApiKey($STRIPE_SECRET);

        $charge_id = $charge_params['charge_id'];
        $total_in_cent = $charge_params['total_in_cent'];
        
        $charge = \Stripe\Charge::retrieve($charge_id,
            [
                'amount' => $total_in_cent,
            ]);
               
        try{

            $capture = $charge->capture();
        }
        catch(\Exception $e){
                                    
            
            Log::Info("CAPTURE FAILED: ". $e->getMessage());

            $charge = $this->createStripeCharge($charge_params);
                        
            Log::Info("CHARGE create AGAIN: " . json_encode($charge));

            if (!empty($charge)) {

                $stripeData = ['charge_id' => $charge['id'],'transaction_id' => $charge['balance_transaction']];
                $updateStripeData = Bookings::SaveChargesStripeData($stripeData, $charge_params['booking_id']);

                $capture = $charge->capture();
            }
            
        }
        
       Log::Info("Capture Stripe charge: " . json_encode($capture));
       return $charge;
    }

    /**
    * Function to calculate the charges which goes to skep home
    * Need to pay by home owner
    */
    public function getTotalChargesWithSkepPercent($charge){
        
        $percent = $this->skep_percent;
        $skep_charge_amount = $this->amountToFloat($charge * ($percent / 100));
        $total = $this->amountToFloat($charge + $skep_charge_amount);

        $skep_charge_amount_in_cent = $skep_charge_amount*100;
        $total_in_cent = $total*100;
        
        return array('skep_charge_amount'=> $skep_charge_amount,
                    'skep_charge_amount_in_cent' => $skep_charge_amount_in_cent,
                    'total' => $total,                    
                    'total_in_cent' => $total_in_cent
                );
    }
    
    public function amountToFloat($amount)
    {    
        return number_format($amount,2,'.','');
        
    }
    public function uniquecode($length = 6)
    {    
        $random= "";
        $data = "";
        srand((double)microtime()*1000000);
                
        $data .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";        
        $data .= "012345678900987654321";        
        
        for($i = 0; $i < $length; $i++)
        {
                $random .= substr($data, (rand()%(strlen($data))), 1);
        }
        return $random;
        
        
    }
    public function uniquecode_nunber($length = 4)
    {    
        $random= "";
        $data = "";
        srand((double)microtime()*1000000);
                      
        $data .= "012345678900987654321";        
        
        for($i = 0; $i < $length; $i++)
        {
                $random .= substr($data, (rand()%(strlen($data))), 1);
        }
        return $random;
        
        
    }

    function applyReferralCode($referral_code,$referral_user_type)
    {
        $referral_id = $referral_balance = 0;

        $where = ['unique_code' => $referral_code, 'user_type' => $this->userType,'status'=>1, 'account_blocked' => 0];
        $check_referral_exist = Users::where($where)->first();
        Log::Info("check_referral_exist where: ".json_encode($where));
        Log::Info("check_referral_exist: ".json_encode($check_referral_exist));

        if(!empty($check_referral_exist)){
            $referral_id = (int)$check_referral_exist['id'];
            $referral_balance = $check_referral_exist['referral_balance'];
            
        }
        return array('referral_id'=>$referral_id,'referral_balance'=>$referral_balance);
    }

    /*
     * @param receiver_id, sender_id and user_type
     * store referral earnings 
     * for receiver its active
     * for sender it will active when receiver use the referral earnings 
     */

    public function earnReferralAmount($receiver_id,$sender_id,$user_type)
    {    
        
        if($user_type == 'homeOwner')
        {
            $receiver_earning = env("HOMEOWNER_REFERRAL_RECEIVER_EARNING");
            $sender_earning = env("HOMEOWNER_REFERRAL_SENDER_EARNING");
        }
        else
        {
            $receiver_earning = env("CLEANER_REFERRAL_RECEIVER_EARNING");
            $sender_earning = env("CLEANER_REFERRAL_SENDER_EARNING");
        }
        $referral_group = time().$receiver_id.$sender_id;


        // receiver earining entry
        
        $data = array();
        $data['user_id'] = $receiver_id;
        $data['referral_type'] = 'receiver';
        $data['referral_amount'] = $receiver_earning;
        $data['is_active'] = 1;
        $data['is_used'] = 0;
        $data['reference_user_activity_id'] = $receiver_id;
        $data['referral_group'] = $referral_group;
        UserReferralHistories::create($data);

        if($user_type == 'homeOwner')
        {
            // receiver referral balance update, first time user signup so receiver_earning is his current referral balance
            Users::where('id', $receiver_id)->update(['referral_balance' => $this->amountToFloat($receiver_earning)]);
        }
        // sender  earining entry
        $data = array();
        $data['user_id'] = $sender_id;
        $data['referral_type'] = 'sender';
        $data['referral_amount'] = $sender_earning;
        $data['is_active'] = 0;
        $data['is_used'] = 0;
        $data['reference_user_activity_id'] = $receiver_id;
        $data['referral_group'] = $referral_group;        
        UserReferralHistories::create($data);
        
        // sender referral balance update
        // sender referral balance is not update here.
        // sender referral balance update when receiver use the referral balance
        
    }

    /*
     * @param user_id as home owner id
     * check if referral amount is available or not
     * if available then apply 
     */

    public function applyReferralAmount($user_id)
    {    
        $referral_data = [];
        $is_applicable = false;
        $where = ['user_id' => $user_id, 'is_active' => 1,'is_used' => 0,'referral_type'=>'receiver'];
        $check_referral = UserReferralHistories::where($where)->first();       
        if(!empty($check_referral))
        {
            $is_applicable = true;
            $referral_data = array(
                                    'referral_id'=>$check_referral['id'],
                                    'referral_amount'=>$check_referral['referral_amount'],                                    
                                    'referral_group'=>$check_referral['referral_group'],
                                    'referral_type'=>$check_referral['referral_type']
                                );

        }
        /*else
        {
            $where = ['user_id' => $user_id, 'is_active' => 1,'referral_type'=>'sender'];
            $check_referral = UserReferralHistories::where($where)->first();       
            if(!empty($check_referral))
            {
                $is_applicable = true;
                $referral_data = array(
                                        'referral_id'=>$check_referral['id'],
                                        'referral_amount'=>$check_referral['referral_amount'],                                    
                                        'referral_group'=>$check_referral['referral_group'],
                                        'referral_type'=>$check_referral['referral_type']
                                    );
            }
        }    
        */    
        return array('is_applicable'=>$is_applicable,'referral_data'=>$referral_data);
    }


    function processPayment($get_booking,$cusId,$owner_details)
    {
        $is_error = 0;
        $err_msg = '';
        $stripeData = [];
        
        $booking_price = $get_booking['booking_price'];
 
        $referral_detail = $this->applyReferralAmount($get_booking['user_id']);

        
        $calculate_charges = $this->getTotalChargesWithSkepPercent($booking_price);

        $skep_charge_amount = $calculate_charges['skep_charge_amount'];
        $skep_charge_amount_in_cent = $calculate_charges['skep_charge_amount_in_cent'];
        $total = $calculate_charges['total'];
        $total_in_cent = $calculate_charges['total_in_cent'];
        
        $referral_balance = $owner_details['referral_balance'];
        $remaining_referral_balance = 0;
        $payable_amount = 0;
        $used_referral_balance = 0;
        if($referral_balance >= $total)
        {
            $used_referral_balance = $total;
            $remaining_referral_balance = $referral_balance - $used_referral_balance;
            
        }
        else{

            $used_referral_balance = $referral_balance;
            $payable_amount = $total - $used_referral_balance;
            
        }

        Log::Info("======================");
        Log::Info("calculate_charges : " . json_encode($calculate_charges));
        Log::Info("======================");
        
        $customer = '';
        $description = '';
        $payment_method = '';
                
        if($payable_amount > 0)
        {

            $total_in_cent = $payable_amount*100;

            $charge_params = array();
            $charge_params['total_in_cent'] = $total_in_cent;
            $charge_params['cusId'] = $cusId;
            $charge_params['first_name'] = $owner_details['first_name'] ;
            $charge_params['email'] = $owner_details['email'] ;
            $charge_params['job_id'] = $get_booking['job_id'];
            
            $charge = $this->createStripeCharge($charge_params);
            if (!empty($charge)) {
                // updating booking table with charge id and transaction id
                $stripeData = ['charge_id' => $charge['id'], 
                                'amount_paid' => (string) $this->amountToFloat($payable_amount), 
                                'transaction_id' => $charge['balance_transaction'], 
                                'paid_status' => 'paid',
                                'skep_charge_amount' => $skep_charge_amount,
                                'referral_discount' => $this->amountToFloat($used_referral_balance)
                            ];
               
                $customer = $charge['customer'];
                $description = $charge['description'];
                $payment_method = $charge['payment_method'];
                                
            } else {
               // $this->error("Some issue has occured with Stripe. Not able to deduct at this time.");
                $is_error = 1;
                $err_msg = "Some issue has occured with Stripe. Not able to deduct at this time.";
    
            }
        }
        else
        {
           
            $stripeData = [
                            'amount_paid' => (string) $this->amountToFloat($payable_amount),                             
                            'paid_status' => 'paid',
                            'skep_charge_amount' => $skep_charge_amount,
                            'referral_discount' => $this->amountToFloat($used_referral_balance)
                        ];
            
                        
        }   
        if($is_error == 0)     
        {
            $updateStripeData = Bookings::SaveChargesStripeData($stripeData, $get_booking['id']);
            if ($updateStripeData) 
            {                
                $stripeData['customer'] = $customer;
                $stripeData['description'] = $description;
                $stripeData['payment_method'] = $payment_method;
                
                /**** Referral data update */
                Users::where('id', $owner_details['id'])->update(['referral_balance' => $this->amountToFloat($remaining_referral_balance)]);
                
                if($referral_detail['is_applicable'])
                {
                    

                    $referral_data = $referral_detail['referral_data'];                                                

                    if($referral_data['referral_type'] == 'receiver')
                    {
                        UserReferralHistories::where('id', $referral_data['referral_id'])->update(['is_used' => 1]);                        
                        
                        $sender_ref_detail = UserReferralHistories::where(['referral_group'=> $referral_data['referral_group'],'referral_type'=>'sender','is_active'=>0])->first();
                        if($sender_ref_detail)
                        {
                            UserReferralHistories::where('id', $sender_ref_detail['id'])->update(['is_active' => 1]);

                            $sender_id = $sender_ref_detail['user_id'];
                            $sender_user_details = Users::where('id', $sender_id)->first();

                            $sender_referral_balance = $sender_user_details['referral_balance'] + $sender_ref_detail['referral_amount'];
                            $update_data = Users::where(['id' => $sender_id])->update(['referral_balance' => $sender_referral_balance]);
                            

                        }
                        
                    }
                }
                
                
            } 
            else 
            {

                $is_error = 1;
                $err_msg = "Stripe data not saved in db.";

            }
        }
                            
        return array('is_error'=>$is_error,'err_msg'=>$err_msg,'charges'=>$stripeData);
    }

    function stripe_platform_balance()
    {
        $STRIPE_SECRET = $this->getStripeKey();
        \Stripe\Stripe::setApiKey($STRIPE_SECRET);

        $platform_account_balance = \Stripe\Balance::retrieve();
        
        $platform_balances = $platform_account_balance->available;
        $platform_total_available_balance = 0;
        if($platform_balances)
        {
            foreach($platform_balances as $key=>$balance)
            {
                $platform_total_available_balance += $balance->amount;
            }
        }
        
        return $platform_total_available_balance;
    }

    /** 
     * this is for cleaner referral income 
     * param: user_id = cleaner_id
     * it will check if cleaner used any reffral_code and earn referral_amount
     * then oncomplete (markAsComplete) booking 
     * receiver cleaner (is_active = 1, is_used = 0 and referral_type = receiver)
     * if platform balance is avaailable then transfer to trainer stripe account 
     * else platform balance is not avaailable then store in cleaner referral_balance 
     * and referral_balance transfer to trainer stripe account once in a week
     * sender cleaner update to is_active = 1 and is_used = 0 and referral_amount updated in sender referral_balance
     * referral_balance send to trainer stripe acocunt once in week 
     * 
     * referral_type = sender does not need to check as in time receive use referral_code sender
     * balance update in cleaner (users table) referral_balance
     * IN cron: 
     * transfer referral_balance to stripe account once in a week
     */
    public function applyCleanerReferralAmount($user_id)
    {   
         
        $referral_data = [];
        $send_transfer = false;
        $where = ['user_id' => $user_id, 'is_active' => 1, 'is_used' => 0 ,'referral_type'=>'receiver'];
        $check_referral = UserReferralHistories::where($where)->first();       
        if(!empty($check_referral))
        {
            
            $referral_amount = $check_referral['referral_amount'];
            $referral_amount_in_cent = $referral_amount*100;

            $cleaner_detail = Users::where(['id' => $user_id])->first();       
            $referral_balance = $cleaner_detail['referral_balance'];

            $stripeUserDetails = StripeUserDetails::where(['user_id' => $user_id])->first();
            if(!empty($stripeUserDetails) && !empty($stripeUserDetails->account_id)){
                $cleanerActId = $stripeUserDetails->account_id;

                $platform_balance = $this->stripe_platform_balance();

                if($platform_balance > $referral_amount_in_cent)
                {
                    $platform_transfer = \Stripe\Transfer::create([
                        "amount" => $referral_amount_in_cent,
                        "currency" => "cad",
                        "destination" => $cleanerActId,
                        "description" =>"Referral credit - ".$referral_balance
                        ]);
                    Log::info("platform_transfer :".json_encode($platform_transfer));    

                    if($platform_transfer)
                    {
                        $send_transfer = true;
                       // $platform_transfer_id = $platform_transfer['id'];
                    }
                }
            }
            
            if($send_transfer == false)
            {
                $updated_referral_balance = $referral_balance + $check_referral['referral_amount'];
                Users::where('id', $user_id)->update(['referral_balance' => $this->amountToFloat($updated_referral_balance)]);

            }
            UserReferralHistories::where('id', $check_referral['id'])->update(['is_used' => 1]);                                    

            $sender_referral = UserReferralHistories::where(['referral_group'=> $check_referral['referral_group'],'referral_type'=>'sender'])->first();
            if(!empty($sender_referral))
            {                
                $sender_referral_cleaner_id = $sender_referral['user_id'];
                $sender_cleaner_detail = Users::where(['id' => $sender_referral_cleaner_id])->first();                       
                $sender_updated_referral_balance = $sender_cleaner_detail['referral_balance'] + $sender_referral['referral_amount'];
                
                Users::where('id', $sender_referral_cleaner_id)->update(['referral_balance' => $this->amountToFloat($sender_updated_referral_balance)]);
                UserReferralHistories::where('id', $sender_referral['id'])->update(['is_active' => 1,'is_used'=>1]);                                    
            }
            

        }
                
        return true;
    }

    function sendSms($phone_number,$sms_content)
    {

        $client = new Client(env("TWILIO_ACCOUNT_SID"), env("TWILIO_AUTH_TOKEN"));
        $sms_status = $client->messages->create(
            $phone_number,
            array(
                'from' => env("TWILIO_NUMBER"),
                'body' => $sms_content
            )
        );
        return true;
    }

    function x_week_range($date) {
        $dayp = date('N',strtotime($date)); //for ex today is wednesday = 3
        $start = $dayp - 1; // 3 - 1 = 2 => wed - 2 days = Mon
        $end = 7 - $dayp; // 7 - 3 = 4 => wed + 4 days = Sun
        
        $start_date = date("Y-m-d", strtotime('- '.$start.' days', strtotime($date)));
        $end_date = date("Y-m-d", strtotime('+ '.$end.' days', strtotime($date)));        

        return array($start_date,$end_date);
    }
    function cleanPhoneNumber($phone)
    {
        $country_code = env('SMS_COUNTRY_CODE');
        
        if(strlen($phone) > 10)
        {
            $phone = str_replace(array($country_code,'(',')','-',' '),array('','','','',''),$phone);
        }
        return trim($phone);
    }

    
    /**
     * This is to check if user has all 3 information
     * selfie_image, government_id_image_front, government_id_image_back
     * if user has 3 information then  authenticate_status = 1
     */

    
    function updateAuthStatus($user_id)
    {
        $where = ['id' => $user_id];
        $user_details = Users::select(
            'id',
            'selfie_image',
            'government_id_image_front',
            'government_id_image_back')->where($where)->first();

        Log::Info("\n updateAuthStatus : ".json_encode($user_details));
        if(!empty($user_details))
        {
            $selfie_image = $govt_id_front = $govt_id_back = "";
            if(!empty($user_details['selfie_image']))
            {
                $selfie_image = (!empty($user_details['selfie_image']))?$this->get_authenticate_certificate($user_details['selfie_image'],'selfie_verification'):"";
            }
            
            if(!empty($user_details['government_id_image_front']))
            {
                $govt_id_front = (!empty($user_details['government_id_image_front']))?$this->get_authenticate_certificate($user_details['government_id_image_front'],'authentication_certificates'):"";
            }
            if(!empty($user_details['government_id_image_back']))
            {
                $govt_id_back = (!empty($user_details['government_id_image_back']))?$this->get_authenticate_certificate($user_details['government_id_image_back'],'authentication_certificates'):"";
            }

            Log::Info("\n selfie_image : ".$selfie_image);
            Log::Info("\n govt_id_front : ".$govt_id_front);
            Log::Info("\n govt_id_back : ".$govt_id_back);

            if($selfie_image != "" && $govt_id_front != "" && $govt_id_back != "")
            {
                $auth_update = Users::where(['id'=>$user_id])
                    ->update(['authenticate_status'=>1]);
                    

            }
        }
        return true;
    }

    function generateToken($user_id,$user_type,$apiToken)
    {
        $token = Token::where(['user_id' => $user_id, 'user_type' => $user_type])->first();
        if (empty($token)) {
            $token = new Token;
        }
        $expire_date_time = date("Y-m-d H:i:s", strtotime("+7 days"));
        $token->user_id = $user_id;
        $token->user_type = $user_type;
        $token->access_token = $apiToken;
        $token->token_status = 1;
        $token->expires_in = $expire_date_time;
        $token->save();
    }

    /*
     * Function to get distance between cleaner and owner's location
     * Used in instant booking
     */
    function getDistanceDiffMins($values)
    {
        $google_api_key = env("GOOGLE_MAP_API_KEY");
        $geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $values['cleaner_lat'] . ',' . $values['cleaner_long'] . '&destinations=' . $values['owner_lat'] . ',' . $values['owner_long'] . '&mode=driving&language=en-EN&sensor=false&key='.$google_api_key);
        $output = json_decode($geocodeFromAddr);
        if ($output->rows[0]->elements[0]->status != 'ZERO_RESULTS' && $output->rows[0]->elements[0]->status != 'NOT_FOUND') {
            $distance = $output->rows[0]->elements[0]->distance->value;
            $time = $output->rows[0]->elements[0]->duration->value;
        } else if ($output->rows[0]->elements[0]->status == 'ZERO_RESULTS') {
            $distance = 0;
            $time = 0;
        } else if ($output->rows[0]->elements[0]->status == 'NOT_FOUND') {
            $distance = 'NOT NEAR';
            $time = 'Too Far';
        }
        return ['distance' => $distance, 'time' => $time];
    }

    /*
     * Function to get about place details     
     */
    function getPlaceDetails($place_id)
    {
        $google_api_key = env("GOOGLE_PLACE_API_KEY");
        $geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?place_id='.$place_id.'&fields=address_components,formatted_address,geometry&key='.$google_api_key);
        $output = json_decode($geocodeFromAddr,true);

        $is_error = 1;
        $place_detail = [];
        if($output['status'] == 'OK')
        {
            $is_error = 0;
            $formatted_address = $output['result']['formatted_address'];
            $address_components = $output['result']['address_components'];

            $place_detail['address'] = $formatted_address;

            
            foreach($address_components as $addressPart) {
                if ((in_array('locality', $addressPart['types'])) && (in_array('political', $addressPart['types'])))
                {
                    $place_detail['city'] = $addressPart['long_name'];
                }
                 if ((in_array('administrative_area_level_1', $addressPart['types'])) && (in_array('political', $addressPart['types'])))
                 {
                    $place_detail['state'] = $addressPart['long_name'];
                 }
                 if ((in_array('country', $addressPart['types'])) && (in_array('political', $addressPart['types'])))
                 {
                    $place_detail['country'] = $addressPart['short_name'];
                 }
                 if ((in_array('postal_code', $addressPart['types'])))
                 {
                    $place_detail['postal_code'] = $addressPart['long_name'];
                 }
            }
           

        }
        return ['is_error' => $is_error, 'place_detail' => $place_detail];
    }

    /**
     * create booking
     * param: all $input and timezone
     * return booking_id 
     */
    function createBooking($input,$timezone)
    {
        $job_id = $this->generateUniqueJobID();

        $service_start_time_utc = $input['service_start_time'];
        $service_end_time_utc = $input['service_end_time'];
        $booking_date_utc = $input['booking_date'];

        log::Info(" createBooking PARAM ::: ".json_encode($input));

        $booking_date_utc = date('Y-m-d', strtotime($service_start_time_utc));        
        
        $instructions = (!empty($input['special_instructions'])) ? $input['special_instructions'] : "";
        $services = (!empty($input['booking_services'])) ? $input['booking_services'] : "";
        $array = [
            'user_id' => $this->userId,
            'service_provider_id' => '',
            'space_id' => $input['space_id'],
            'search_work_region' => $input['area_of_region'],
            'booking_services' => $services,
            'booking_date' => $booking_date_utc,
            'service_start_time' => $service_start_time_utc,
            'service_end_time' => $service_end_time_utc,
            'booking_hours' => $input['booking_hours'],
            'booking_price' => $this->amountToFloat($input['booking_price']),
            'booking_type' => $input['booking_type'],            
            'booking_address' => $input['booking_address'],
            'latitude' => $input['latitude'],
            'longitude' => $input['longitude'],
            'special_instructions' => $instructions,
            'booking_status' => 0,
            'is_cancelled' => 0,
            'job_id' => $job_id,
           
        ];

        if($input['booking_type'] == 'advanced')
        {
            $array['booking_frequency'] = $input['booking_frequency'];
            $array['mass_blast_search'] = $input['mass_blast_search'];
            
        }
        
        $create_bookings = Bookings::create($array);
        return $create_bookings['id'];

    }

    function isCleanerBusy($cleaner_id,$service_start_time,$service_end_time,$booking_type='advanced',$booking_id = 0)
    {
        if($booking_type == 'advanced')
        {
            $previous_buffer_time = env("ADVANCE_BOOKING_START_BUFFER_TIME");
            $next_buffer_time = env("ADVANCE_BOOKING_END_BUFFER_TIME");

        }
        else
        {
            $previous_buffer_time = env("INSTANT_BOOKING_START_BUFFER_TIME");
            $next_buffer_time = env("INSTANT_BOOKING_END_BUFFER_TIME");

        }        
        
        $prev_start_time = date('Y-m-d H:i:s',strtotime ( '-'.$previous_buffer_time.' minutes' , strtotime ( $service_start_time ) ) );
        $next_end_time = date('Y-m-d H:i:s',strtotime ( '+'.$next_buffer_time.' minutes' , strtotime ( $service_end_time ) ) );

        Log::Info(" BUFFER TIME : ".$previous_buffer_time.'=='.$next_buffer_time);
        Log::Info(" ORG TIME : ".$service_start_time.'=='.$service_end_time);
        Log::Info(" PREV AND NEXT TIME : ".$prev_start_time.'=='.$next_end_time);
        
        $check_booking_sql = "
        SELECT * FROM `bookings` WHERE 
`bookings`.`service_provider_id` = ".$cleaner_id."  AND   
        ( 
            (
                (`bookings`.`service_start_time` between '".$prev_start_time."' AND '".$next_end_time."') OR 
                (`bookings`.`service_end_time` between '".$prev_start_time."' AND '".$next_end_time."')
            ) OR 
            (
                ('".$prev_start_time."' between `bookings`.`service_start_time` AND `bookings`.`service_end_time`) OR
                ('".$next_end_time."' between `bookings`.`service_start_time` AND `bookings`.`service_end_time`)
            ) 
        ) AND 
        `bookings`.`booking_status` = '1' AND 
        `bookings`.`is_cancelled` = 0 AND 
        `bookings`.`is_orphan_booking` = 0 ";
        
        if($booking_id > 0)
        {
            $check_booking_sql = " AND `bookings`.`id` != ".$booking_id;   
        }
        Log::Info(" isCleanerBusy check_booking_sql : ".$check_booking_sql);

        $check_bookings_exists = DB::select($check_booking_sql);

        $check_bookings_count = count($check_bookings_exists);
        if($check_bookings_count)
            $is_busy = 1;
        else
            $is_busy = 0;

        Log::Info(" isCleanerBusy PARAM :". $cleaner_id.' '.$service_start_time.' '.$service_end_time.'=='.$is_busy);
        Log::Info(" isCleanerBusy : ". $is_busy);

        return $is_busy;
    }

    function isHomeOwnerBookingExists($user_id,$space_id,$service_start_time,$service_end_time,$booking_type='advanced')
    {
                
        $check_bookings_exists = DB::select("
            SELECT * FROM `bookings` WHERE 
            `bookings`.`user_id` = ".$user_id."  AND 
            `bookings`.`space_id` = ".$space_id."  AND 
            ( 
                (
                    (`bookings`.`service_start_time` between '".$service_start_time."' AND '".$service_end_time."') OR 
                    (`bookings`.`service_end_time` between '".$service_start_time."' AND '".$service_end_time."')
                ) OR 
                (
                    ('".$service_start_time."' between `bookings`.`service_start_time` AND `bookings`.`service_end_time`) OR
                    ('".$service_end_time."' between `bookings`.`service_start_time` AND `bookings`.`service_end_time`)
                ) 
            ) AND 
            `bookings`.`booking_status` = '1' AND 
            `bookings`.`is_cancelled` = 0 AND
            `bookings`.`is_orphan_booking` = 0");

        $check_bookings_count = count($check_bookings_exists);
        if($check_bookings_count)
            $is_busy = 1;
        else
            $is_busy = 0;

        //Log::Info(" isHoneOwner Exist Booking :". $cleaner_id.' '.$service_start_time.' '.$service_end_time.'=='.$is_busy);
        Log::Info(" isCleanerBusy : ". $is_busy);

        return $is_busy;
    }

    /**
     * Current Booking:
     * Duration: 4 Hrs
     * Start Time: 2:00 pm
     * End Time:  6:00 pm
     * 
     * Proposed Booking:	
     * Duration: 3 Hrs		
     * Start Time: 10 AM (9 AM)
     * End Time: 1 PM (2 PM)
     * by default the system will check conflicts and busy slot for 
     * any cleaner in mass blast
     */

    function sendRequestToRecentClosedCleaners($input,$timezone,$distanceNeedToMatch)
    {
               
        $cleaner_ids = $list_of_cleaners = $tokens = array();                                 

        $is_notify = 0;
        $booking_id = 0;
        
        if(array_key_exists('booking_id',$input) && $input['booking_id']>0)
        {
            $booking_id = $input['booking_id'];
        
            $where_array = ['id' => $booking_id, 'booking_status' => '0', 'booking_type' => 'advanced'];
            $get_booking_details = Bookings::where($where_array)->first(); 
            if(!empty($get_booking_details))
            {
                $user_id = $get_booking_details['user_id'];
                $service_start_time_utc = $get_booking_details['service_start_time'];
                $service_end_time_utc = $get_booking_details['service_end_time'];
                $booking_date_utc = $get_booking_details['booking_date'];

                $latitude = $get_booking_details['latitude'];
                $longitude = $get_booking_details['longitude'];
            
            }
            else
            {
                $this->error("Booking does not exist", ['err' => 0]);
            }
        }
        else
        {
            $user_id = $this->userId;
            $service_start_time_utc = $input['service_start_time'];
            $service_end_time_utc = $input['service_end_time'];
            $booking_date_utc = $input['booking_date'];

            $latitude = $input['latitude'];
            $longitude = $input['longitude'];

        }

        $previous_buffer_time = env("ADVANCE_BOOKING_START_BUFFER_TIME");
        $next_buffer_time = env("ADVANCE_BOOKING_END_BUFFER_TIME");

        $prev_start_time = date('Y-m-d H:i:s',strtotime ( '-'.$previous_buffer_time.' minutes' , strtotime ( $service_start_time_utc ) ) );
        $next_end_time = date('Y-m-d H:i:s',strtotime ( '+'.$next_buffer_time.' minutes' , strtotime ( $service_end_time_utc ) ) );

    
        $exclude_pending_complete_cleaners_sql = $this->get_pending_complete_cleaners_sql();


        $user_availability_sql ="SELECT  B.id, B.user_id,U.email, B.service_start_time, B.service_end_time, 
        B.booking_date, B.service_provider_id , U.rating, B.latitude, B.longitude , 
        ( 3959 * acos 
            (   cos ( radians(".$latitude.") ) * cos( radians( B.latitude ) ) * 
                cos ( radians( B.longitude ) - radians(".$longitude.") ) + 
                sin ( radians(".$latitude.") ) * sin( radians( B.latitude ) ) 
            ) 
        ) * 1609.34 AS radius_in_meter 
        FROM `bookings` B,`users` U WHERE  
        `service_provider_id` > 0 AND        
        ( 
            DATE_FORMAT(B.service_end_time,'%Y-%m-%d %H:%i') = '".$prev_start_time."' OR  
            DATE_FORMAT(B.service_start_time,'%Y-%m-%d %H:%i')= '".$next_end_time."' 
            
        ) AND 
        B.`is_orphan_booking` = 0 AND 
        B.`booking_status` = '1' AND 
        B.`is_cancelled` = 0 AND 
        U. `user_type` = 'cleaner' AND                
        U.`status` = 1 AND 
        U.`account_blocked` = '0' AND          
        B.service_provider_id = U.id ";

        $user_availability_sql .= " AND U.id NOT IN (".$exclude_pending_complete_cleaners_sql.") ";

        if(array_key_exists('exclude_previous_cleaner',$input) && $input['exclude_previous_cleaner']>0)
        {
            $user_availability_sql .= " AND U.id NOT IN (".$input['exclude_previous_cleaner'].")";
        }
        $user_availability_sql .= " HAVING radius_in_meter <= ".$distanceNeedToMatch;
        $user_availability_sql .= " ORDER BY radius_in_meter ASC";

        Log::Info("====");
        Log::Info("sendRequestToRecentClosedCleaners SQL: " . $user_availability_sql);
        Log::Info("====");

        $user_availability_data = DB::select($user_availability_sql);
        $user_availability_counts = count($user_availability_data);
        Log::Info("sendRequestToRecentClosedCleaners total: " . $user_availability_counts);

        if($user_availability_counts > 0)
        {
            foreach ($user_availability_data as $cleaners) {

                $cleaner_id = $cleaners->service_provider_id;                
                $cleaner_ids[] = [
                    'id' => $cleaners->id,
                    'ratings' => $cleaners->rating,
                ];

                                                
                
            }
        }
        
        if (!empty($cleaner_ids)) 
        {
            $is_notify = 1;
            array_multisort(array_column($cleaner_ids, 'ratings'), SORT_DESC, $cleaner_ids);

            foreach ($cleaner_ids as $values) {
                $list_of_cleaners[] = $values['id'];
            }

            Log::Info("================");
            Log::Info("sendRequestToRecentClosedCleaners list_of_cleaners :: " . json_encode($list_of_cleaners));
            Log::Info("================");            

            if($booking_id == 0)
            {
                $booking_id = $this->createBooking($input,$timezone);                          
            }
            else
            {
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
            }

            
            
            foreach ($list_of_cleaners as $key => $cleaners) {

                $where = ['id' => $cleaners];
                $user_details = Users::where($where)->first();
                if (!empty($user_details)) {

                    $array = ['sender_id' => $user_id, 'receiver_id' => $user_details['id'], 'booking_id' => $booking_id, 'status' => 0];
                    $notification_insert = Notifications::create($array);

                    if($user_details['push_notification'] == 1)
                    {
                        $tokens[] = $user_details['device_token'];                        
                    }
                    
                }

                

            }

            if(!empty($tokens))
            {
                $payload['title'] = 'New Booking';
                $payload['body'] = 'You have new booking.';
                $payload['value'] = $booking_id;
                $payload['type'] = 'advanced_mass_blast';
                $payload['user_type'] = 'cleaner';

                Log::Info("================");
                Log::Info("sendRequestToRecentClosedCleaners tokens :: " . json_encode($tokens));
                Log::Info("================");

                $this->send_advanced_booking_notification($tokens, $payload);

            }
            
            

        }
        else
        {
            if($booking_id > 0)
            {
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
            }
        }
        return array('is_notify'=>$is_notify,'booking_id'=>$booking_id);

    }

    function getCleanersWithPreviousClean($input,$timezone,$distanceNeedToMatch)
    {

        $previous_buffer_time = env("ADVANCE_BOOKING_START_BUFFER_TIME");
        $next_buffer_time = env("ADVANCE_BOOKING_END_BUFFER_TIME");
        
        $cleaner_ids = $list_of_cleaners = $tokens = array();                                 
        
        $is_notify = 0;
        $booking_id = 0;
        
        if(array_key_exists('booking_id',$input) && $input['booking_id']>0)
        {
            $booking_id = $input['booking_id'];
        
            $where_array = ['id' => $booking_id, 'booking_status' => '0', 'booking_type' => 'advanced'];
            $get_booking_details = Bookings::where($where_array)->first(); 
            if(!empty($get_booking_details))
            {
                $user_id = $get_booking_details['user_id'];
                $service_start_time_utc = $get_booking_details['service_start_time'];
                $service_end_time_utc = $get_booking_details['service_end_time'];
                $booking_date_utc = $get_booking_details['booking_date'];

                $service_start_time_user = $this->utcTimeToUserTime($service_start_time_utc, $timezone);
                $service_end_time_user = $this->utcTimeToUserTime($service_end_time_utc, $timezone);

                $latitude = $get_booking_details['latitude'];
                $longitude = $get_booking_details['longitude'];
            
            }
            else
            {
                $this->error("Booking does not exist", ['err' => 0]);
            }
        }
        else
        {
            $user_id = $this->userId;
            $service_start_time_user = $input['service_start_time_user'];
            $service_end_time_user = $input['service_end_time_user'];

            $service_start_time_utc = $input['service_start_time'];
            $service_end_time_utc = $input['service_end_time'];

            $booking_date_utc = $input['booking_date'];

            $latitude = $input['latitude'];
            $longitude = $input['longitude'];

        }

        
        $booking_date_start_hour_user  = date('Y-m-d 00:00',strtotime($service_start_time_user));
        $booking_date_end_hour_user  = date('Y-m-d 23:59',strtotime($service_start_time_user));
        
        $booking_date_start_hour_utc = $this->userTimeToUTCTime($booking_date_start_hour_user, $timezone);
        $booking_date_end_hour_utc = $this->userTimeToUTCTime($booking_date_end_hour_user, $timezone);
        
       
        $prev_start_time = date('Y-m-d H:i:s',strtotime ( '-'.$previous_buffer_time.' minutes' , strtotime ( $service_start_time_utc ) ) );
        $next_end_time = date('Y-m-d H:i:s',strtotime ( '+'.$next_buffer_time.' minutes' , strtotime ( $service_end_time_utc ) ) );

        
        $exclude_pending_complete_cleaners_sql = $this->get_pending_complete_cleaners_sql();

        $user_availability_sql ="SELECT  B.id, B.user_id,U.email, B.service_start_time, B.service_end_time, 
        B.booking_date, B.service_provider_id , U.rating, B.latitude, B.longitude , 
        ( 3959 * acos 
            (   cos ( radians(".$latitude.") ) * cos( radians( B.latitude ) ) * 
                cos ( radians( B.longitude ) - radians(".$longitude.") ) + 
                sin ( radians(".$latitude.") ) * sin( radians( B.latitude ) ) 
            ) 
        ) * 1609.34 AS radius_in_meter 
        FROM `bookings` B,`users` U WHERE          
        ( 
            (
                DATE_FORMAT(B.service_end_time,'%Y-%m-%d %H:%i') <= '".$prev_start_time."' AND
                DATE_FORMAT(B.service_end_time,'%Y-%m-%d %H:%i') >= '".$booking_date_start_hour_utc."' 
            )            
            OR  
            (
                DATE_FORMAT(B.service_start_time,'%Y-%m-%d %H:%i') >= '".$next_end_time."' AND
                DATE_FORMAT(B.service_start_time,'%Y-%m-%d %H:%i') <= '".$booking_date_end_hour_utc."' 
            )                        
        ) AND 
        B.`service_provider_id` > 0 AND  
        B.`is_orphan_booking` = 0 AND 
        B.`is_cancelled` = 0 AND 
        B.`booking_status` = '1' AND 
        U. `user_type` = 'cleaner' AND 
        U.`status` = 1 AND 
        U.`account_blocked` = '0' AND          
        B.service_provider_id = U.id ";
                
        $user_availability_sql .= " AND U.id NOT IN (".$exclude_pending_complete_cleaners_sql.") ";

        if(array_key_exists('exclude_previous_cleaner',$input) && $input['exclude_previous_cleaner']>0)
        {
            $user_availability_sql .= " AND U.id NOT IN (".$input['exclude_previous_cleaner'].") ";
        }        

        if($booking_id > 0)
        {            
            $user_availability_sql .= " AND U.id NOT IN (SELECT receiver_id from notifications WHERE booking_id = '".$booking_id."') ";

        }
        $user_availability_sql .= " HAVING radius_in_meter <= ".$distanceNeedToMatch;
        $user_availability_sql .= " ORDER BY radius_in_meter ASC";
        
        Log::Info("====");
        Log::Info("getCleanersWithPreviousClean SQL: " . $user_availability_sql);
        Log::Info("====");

        $user_availability_data = DB::select($user_availability_sql);
        $user_availability_counts = count($user_availability_data);
        Log::Info("getCleanersWithPreviousClean total: " . $user_availability_counts);

        if($user_availability_counts > 0)
        {
            foreach ($user_availability_data as $cleaners) {

                $cleaner_id = $cleaners->service_provider_id;
               
                $is_busy = $this->isCleanerBusy($cleaner_id,$service_start_time_utc,$service_end_time_utc);
                
                if(!$is_busy)
                {
                    
                    $cleaner_ids[] = [
                        'id' => $cleaners->id,
                        'ratings' => $cleaners->rating,
                    ];
                }
                                                
                
            }
        }
        
        if (!empty($cleaner_ids)) 
        {
            $is_notify = 1;

            array_multisort(array_column($cleaner_ids, 'ratings'), SORT_DESC, $cleaner_ids);

            foreach ($cleaner_ids as $values) {
                $list_of_cleaners[] = $values['id'];
            }

            Log::Info("================");
            Log::Info("getCleanersWithPreviousClean list_of_cleaners :: " . json_encode($list_of_cleaners));
            Log::Info("================");            

            

            if($booking_id == 0)
            {
                $booking_id = $this->createBooking($input,$timezone);      
            }
            else
            {
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
               
            }

            foreach ($list_of_cleaners as $key => $cleaners) {

                $where = ['id' => $cleaners];
                $user_details = Users::where($where)->first();
                if (!empty($user_details)) {

                    $array = ['sender_id' => $user_id, 'receiver_id' => $user_details['id'], 'booking_id' => $booking_id, 'status' => 0];
                    $notification_insert = Notifications::create($array);

                    if($user_details['push_notification'] == 1)
                    {
                        $tokens[] = $user_details['device_token'];                        
                    }
                    
                }

                

            }

            if(!empty($tokens))
            {
                $payload['title'] = 'New Booking';
                $payload['body'] = 'You have new booking.';
                $payload['value'] = $booking_id;
                $payload['type'] = 'advanced_mass_blast';
                $payload['user_type'] = 'cleaner';

                Log::Info("================");
                Log::Info("sendRequestToRecentClosedCleaners tokens :: " . json_encode($tokens));
                Log::Info("================");

                $this->send_advanced_booking_notification($tokens, $payload);

            }
            
            

        }
        else
        {
            if($booking_id > 0)
            {                
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
            }
        }
        
        return array('is_notify'=>$is_notify,'booking_id'=>$booking_id);
    }
    
    function getCleanersWithinPreferredWorkArea($input,$timezone)
    {
      
        $cleaner_ids = $list_of_cleaners = array();

        $is_notify = 0;
        $booking_id = 0;
        
        if(array_key_exists('booking_id',$input) && $input['booking_id']>0)
        {
            $booking_id = $input['booking_id'];
        
            $where_array = ['id' => $booking_id, 'booking_status' => '0', 'booking_type' => 'advanced'];
            $get_booking_details = Bookings::where($where_array)->first(); 
            if(!empty($get_booking_details))
            {
                $user_id = $get_booking_details['user_id'];
                $service_start_time_utc = $get_booking_details['service_start_time'];
                $service_end_time_utc = $get_booking_details['service_end_time'];
                $booking_date_utc = $get_booking_details['booking_date'];

                $latitude = $get_booking_details['latitude'];
                $longitude = $get_booking_details['longitude'];
                $area_of_region = $get_booking_details['search_work_region'];
            }
            else
            {
                $this->error("Booking does not exist", ['err' => 0]);
            }
        }
        else
        {
            $user_id = $this->userId;
            $service_start_time_utc = $input['service_start_time'];
            $service_end_time_utc = $input['service_end_time'];
            $booking_date_utc = $input['booking_date'];

            $latitude = $input['latitude'];
            $longitude = $input['longitude'];
            $area_of_region = $input['area_of_region'];
        }

        Log::Info("\n++++++++++++++++++++getCleanersWithinPreferredWorkArea INPUT".json_encode($input));


        $exclude_pending_complete_cleaners_sql = $this->get_pending_complete_cleaners_sql();
        
        $sql = "SELECT * FROM `users` WHERE `user_type` = 'cleaner' AND 
                            `status` = 1 AND 
                            `account_blocked` = '0' AND 
                            FIND_IN_SET('" . $area_of_region . "',work_area) ";
        
        $sql .= " AND id NOT IN (".$exclude_pending_complete_cleaners_sql.") ";
        
        if(array_key_exists('exclude_previous_cleaner',$input) && $input['exclude_previous_cleaner']>0)
        {
            $sql .= " AND id NOT IN (".$input['exclude_previous_cleaner'].") ";
        }
        
        if($booking_id > 0)
        {            
            $sql .=  " AND id NOT IN (SELECT receiver_id from notifications WHERE booking_id = '".$booking_id."')";

        }

        Log::Info("\n =============== \n getCleanersWithinPreferredWorkArea SQL: " . $sql);
        $check_cleaner = DB::select($sql);

        $cleaner_counts = count($check_cleaner);
        Log::Info("getCleanersWithinPreferredWorkArea cleaner_counts: " . $cleaner_counts);
        
        if ($cleaner_counts > 0) {
            foreach ($check_cleaner as $cleaners) {
             
                $is_busy = $this->isCleanerBusy($cleaners->id,$service_start_time_utc,$service_end_time_utc);
                if(!$is_busy)
                {
                    $cleaner_ids[] = [
                        'id' => $cleaners->id,
                        'ratings' => $cleaners->rating,
                    ];
                }
                
            }
        

        }

        if (!empty($cleaner_ids)) 
        {
            $is_notify = 1;

            array_multisort(array_column($cleaner_ids, 'ratings'), SORT_DESC, $cleaner_ids);

            foreach ($cleaner_ids as $values) {
                $list_of_cleaners[] = $values['id'];
            }

            Log::Info("================");
            Log::Info(" getCleanersWithinPreferredWorkArea list_of_cleaners :: " . json_encode($list_of_cleaners));
            Log::Info("================");

            if($booking_id == 0)
            {
                $booking_id = $this->createBooking($input,$timezone);
                
            }
            else
            {
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   

            }
                    
            foreach ($list_of_cleaners as $key => $cleaners) {

                $where = ['id' => $cleaners];
                $user_details = Users::where($where)->first();
                if (!empty($user_details)) 
                {

                    $array = ['sender_id' => $user_id, 'receiver_id' => $user_details['id'], 'booking_id' => $booking_id, 'status' => 0];
                    $notification_insert = Notifications::create($array);

                    if($user_details['push_notification'] == 1)
                    {
                        $tokens[] = $user_details['device_token'];
                    }
                    
                }

                

            }
            if(!empty($tokens))
            {
                $payload['title'] = 'New Booking';
                $payload['body'] = 'You have new booking.';
                $payload['value'] = $booking_id;
                $payload['type'] = 'advanced_mass_blast';
                $payload['user_type'] = 'cleaner';

                Log::Info("================");
                Log::Info(" getCleanersWithinPreferredWorkArea tokens :: " . json_encode($tokens));
                Log::Info("================");

                $this->send_advanced_booking_notification($tokens, $payload);
                
            }
            

            

        }
        else
        {
            if($booking_id > 0)
            {
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
            }
        }
        
        
        return array('is_notify'=>$is_notify,'booking_id'=>$booking_id);
    }

    function getCleanersGTA($input,$timezone)
    {
        $cleaner_ids = $list_of_cleaners = array();

        $distanceNeedToMatch = env("ADD_ADVANCED_BOOKING_DISTANCE_GTA");
        $is_notify = 0;
        $booking_id = 0;
        
        if(array_key_exists('booking_id',$input) && $input['booking_id']>0)
        {
            $booking_id = $input['booking_id'];
        
            $where_array = ['id' => $booking_id, 'booking_status' => '0', 'booking_type' => 'advanced'];
            $get_booking_details = Bookings::where($where_array)->first(); 
            if(!empty($get_booking_details))
            {
                $user_id = $get_booking_details['user_id'];
                $service_start_time_utc = $get_booking_details['service_start_time'];
                $service_end_time_utc = $get_booking_details['service_end_time'];
                $booking_date_utc = $get_booking_details['booking_date'];

                $latitude = $get_booking_details['latitude'];
                $longitude = $get_booking_details['longitude'];
                $area_of_region = $get_booking_details['search_work_region'];
            }
            else
            {
                $this->error("Booking does not exist", ['err' => 0]);
            }
        }
        else
        {
            $user_id = $this->userId;
            $service_start_time_utc = $input['service_start_time'];
            $service_end_time_utc = $input['service_end_time'];
            $booking_date_utc = $input['booking_date'];

            $latitude = $input['latitude'];
            $longitude = $input['longitude'];
            $area_of_region = $input['area_of_region'];
        }
        
        //$sql = "SELECT * FROM `users` WHERE 
        //                    `user_type` = 'cleaner' AND 
        //                    `status` = 1 AND 
        //                    `account_blocked` = '0' ";
                
        $exclude_pending_complete_cleaners_sql = $this->get_pending_complete_cleaners_sql();

        $user_availability_sql ="SELECT *, 
        ( 3959 * acos 
            (   cos ( radians(".$latitude.") ) * cos( radians( latitude ) ) * 
                cos ( radians( longitude ) - radians(".$longitude.") ) + 
                sin ( radians(".$latitude.") ) * sin( radians( latitude ) ) 
            ) 
        ) * 1609.34 AS radius_in_meter 
        FROM `users` WHERE 
        `user_type` = 'cleaner' AND 
        `status` = 1 AND                 
        `account_blocked` = '0' ";

        $user_availability_sql .= " AND id NOT IN (".$exclude_pending_complete_cleaners_sql.") ";

        if(array_key_exists('exclude_previous_cleaner',$input) && $input['exclude_previous_cleaner']>0)
        {
            $user_availability_sql .= " AND id NOT IN (".$input['exclude_previous_cleaner'].") ";
        }

        if($booking_id > 0)
        {            
            $user_availability_sql .=  " AND id NOT IN (SELECT receiver_id from notifications WHERE booking_id = '".$booking_id."') ";
        }

        $user_availability_sql .= " HAVING radius_in_meter <= ".$distanceNeedToMatch;
        $user_availability_sql .= " ORDER BY radius_in_meter";        
        $user_availability_sql .=  " LIMIT 0,100 ";
        Log::Info("\n =============== \n getCleanersGTA SQL: " . $user_availability_sql);

        $check_cleaner = DB::select($user_availability_sql);
        Log::Info('getCleanersGTA DATA',$check_cleaner);

        $cleaner_counts = count($check_cleaner);
        Log::Info("getCleanersGTA cleaner_counts: " . $cleaner_counts);
        
        if ($cleaner_counts > 0) {
            foreach ($check_cleaner as $cleaners) {
             
                $is_busy = $this->isCleanerBusy($cleaners->id,$service_start_time_utc,$service_end_time_utc);
                if(!$is_busy)
                {
                    //
                    //$latlong = ['cleaner_lat' => $cleaners->latitude, 'cleaner_long' => $cleaners->longitude, 'owner_lat' => $latitude, 'owner_long' => $longitude];
                    //$distanceTime = $this->getDistanceDiffMins($latlong);
                    //$distance = $distanceTime['distance'];
                    //$time = $distanceTime['time'];                                
                    
                    //Log::Info("getCleanersGTA distance: " . $distance . ' == ' . $distanceNeedToMatch);

                    //if ($distance <= $distanceNeedToMatch) {
                    
                    //    $cleaner_ids[] = [
                    //        'id' => $cleaners->id,
                    //        'ratings' => $cleaners->rating,
                    //    ];
                    //}
                    //
                    
                    $cleaner_ids[] = [
                        'id' => $cleaners->id,
                        'ratings' => $cleaners->rating,
                    ];
                    
                }
                
            }
        

        }

        if (!empty($cleaner_ids)) 
        {
            $is_notify = 1;
            array_multisort(array_column($cleaner_ids, 'ratings'), SORT_DESC, $cleaner_ids);

            foreach ($cleaner_ids as $values) {
                $list_of_cleaners[] = $values['id'];
            }

            Log::Info("================");
            Log::Info(" getCleanersGTA list_of_cleaners :: " . json_encode($list_of_cleaners));
            Log::Info("================");
                        
            if($booking_id == 0)
            {
                $booking_id = $this->createBooking($input,$timezone);                        

            }
            else
            {
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
            }
            
            foreach ($list_of_cleaners as $key => $cleaners) {

                $where = ['id' => $cleaners];
                $user_details = Users::where($where)->first();
                if (!empty($user_details)) 
                {

                    $array = ['sender_id' => $user_id, 'receiver_id' => $user_details['id'], 'booking_id' => $booking_id, 'status' => 0];
                    $notification_insert = Notifications::create($array);

                    if($user_details['push_notification'] == 1)
                    {
                        $tokens[] = $user_details['device_token'];
                    }
                    
                }

                

            }

            if(!empty($tokens))
            {
                $payload['title'] = 'New Booking';
                $payload['body'] = 'You have new booking.';
                $payload['value'] = $booking_id;
                $payload['type'] = 'advanced_mass_blast';
                $payload['user_type'] = 'cleaner';

                Log::Info("================");
                Log::Info(" getCleanersGTA tokens :: " . json_encode($tokens));
                Log::Info("================");

                $this->send_advanced_booking_notification($tokens, $payload);

                
            }
            
            

        }
        else
        {
            if($booking_id> 0 )
            {                
                $update = array();
                $update['mass_blast_search'] = $input['mass_blast_search'];                
                $updateStripeData = Bookings::SaveChargesStripeData($update, $booking_id);   
            }
        }

         return array('is_notify'=>$is_notify,'booking_id'=>$booking_id);
    }

    function deleteNotification($booking_id,$exclude_id = "")
    {
        $destroy_notification_sql = "DELETE FROM notifications where booking_id = '" . $booking_id . "'";
        
        if($exclude_id!="" && (int)$exclude_id>0)
        {
            $destroy_notification_sql .= " AND receiver_id!='" . $exclude_id . "'";
        }
        
        Log::Info(" destroy_sql : " . $destroy_notification_sql);
        $qry = DB::delete($destroy_notification_sql);
        
        return true;
    }

    /**
     * this is for temporary to recover the issue
     * this is function to deduct stripe fees from booking price
     * skep commission is calculating in front-end
     * 
     */
    function bookingPriceForCleaner($booking_price)
    {
       // $price = $booking_price - env("STRIPE_FEES");

        return $this->amountToFloat($booking_price);

    }

    function checkValidBookingTime($start_time,$end_time)
    {
        $error = 0;
        $start_hour = date('H:i:s',strtotime($start_time));
        $end_hour = date('H:i:s',strtotime($end_time));

        Log::Info("BOOKING TIME: ".env("SYSTEM_BOOKING_FIRST_BOOK_TIME").'==='.env("SYSTEM_BOOKING_LAST_BOOK_TIME"));
        Log::Info(" SERVICE TIME: ".$start_time.'==='.$end_time);                        
        Log::Info(" USER TIME: ".$start_hour.'=='.$end_hour);

        if((int)env("SYSTEM_BOOKING_TIME_RANGE_ENABLED") == 1)
        {
            
            if(
                strtotime($start_hour) < strtotime(env("SYSTEM_BOOKING_FIRST_BOOK_TIME")) || 
                strtotime($start_hour) > strtotime(env("SYSTEM_BOOKING_LAST_BOOK_TIME"))
            )
            {
                $this->error("Sorry, the cleaners are only available between ".date('h:i A',strtotime(env("SYSTEM_BOOKING_FIRST_BOOK_TIME")))." till ".date('h:i A',strtotime(env("SYSTEM_BOOKING_LAST_BOOK_TIME"))));
            }
        }
        
        
        return true;

    }

    function get_pending_complete_cleaners_sql($service_provider_id = 0)
    {
        $current_time = date('Y-m-d H:i:s');
        $sql = "SELECT DISTINCT (service_provider_id) from bookings WHERE 
            is_completed = 0 AND 
            is_cancelled = 0 AND 
            is_orphan_booking = 0 AND 
            booking_status = '1' AND 
            service_provider_id > 0 AND 
            service_end_time < '".$current_time."' ";

        if($service_provider_id > 0)
        {
            $sql .= " AND service_provider_id = '".$service_provider_id."'";
        }
        
        return $sql ;
    }
 
    

    
}