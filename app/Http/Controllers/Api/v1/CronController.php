<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\admin\Basicservices;
use App\Models\admin\Extraservices;
use App\Models\admin\Regions;
use App\Models\admin\Waiting;
use App\Models\api\v1\Bookings;
use App\Models\api\v1\Favourites;
use App\Models\api\v1\Myspace;
use App\Models\api\v1\Notifications;
use App\Models\api\v1\Ratings;
use App\Models\api\v1\StripeUserDetails;
use App\Models\api\v1\Users;
use Cartalyst\Stripe\Stripe;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Input;
use Mail;
use App\Models\api\v1\Jsons;
use App\Models\api\v1\UserReferralHistories;
class CronController extends Controller
{
    public $succesStatus = 200;
    public $unauthorizedStatus = 401;
    public $userType = "";
    public $apiToken;

    /* Constructor function to get userid, usertype and authrization token in header */

    public function __construct(Request $request)
    {
        

        // Unique Token
        $this->apiToken = uniqid(base64_encode(str_random(20)));
        $this->user_type = $request->header('userType') ? $request->header('userType') : "";
        $this->userId = $request->header('userId') ? $request->header('userId') : "";
        //$this->userId = 345;
        $this->skep_percent = env("SKEP_CHARGES_PERCENT");
        $this->hourly_rate = env("HOURLY_RATE_FOR_SERVICES");
        $this->charge_deduct_cleaner_percent = env("CLEANER_CHARGE_DEDUCTION_PERCENT");
        $this->homeowner_penalty_percent = env("HOMEOWNER_PENALTY");
        $this->cancelled_hrs = env("CANCELLATION_HRS_INS");
        $this->cancelled_hrs_adv = env("CANCELLATION_HRS_ADV");
        $this->add_advanced_booking_distance = env("ADD_ADVANCED_BOOKING_DISTANCE");
        
        $this->cleaner_ratings = env("CLEANER_RATING");
    }

    /*
     * Function used to charge homeowners for advanced bookings before 48hrs.
     * using in cron
     */
    public function processChargesHomeowners()
    {
        $is_error = 0;
        
        $ids = $ids1 = $ids2 = [];

        $advance_payment_process_hrs = env("STRIPE_PAYMENT_HOLD_PROCESS");

        $current_time = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime('+'.$advance_payment_process_hrs.' hours', strtotime($current_time)));        

       // Log::Info("CRON: processChargesHomeowners".$current_time.'==='.$end_date);

        $booking_charge_sal = "SELECT * FROM bookings WHERE 
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') >= '".$current_time."' AND 
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') <= '".$end_date."' AND 
                                `charge_id` IS NULL AND 
                                `service_provider_id` IS NOT NULL AND 
                                `service_provider_id` != '' AND 
                                `is_cancelled` = 0 AND 
                                `is_completed` = 0";
        Log::Info("CRON: SQL: ".$booking_charge_sal);                        
        $getUpcomingBookings = DB::select($booking_charge_sal);
        if ($getUpcomingBookings) {
            
            foreach ($getUpcomingBookings as $bookings) {
                
                $bookings_id = $bookings->id;
                $user = Users::where('id', $bookings->user_id)->first();

                $referral_balance = $user['referral_balance'];
                
                // Checking customer id correspond to home owner
                $stripeUserDetails = StripeUserDetails::where(['user_id' => $bookings->user_id])->first();
                if ($stripeUserDetails) {
                    $cusId = $stripeUserDetails->customer_id;
                } else {
                    $ids[] = $bookings_id;
                }
                
                $booking_price = $bookings->booking_price;
                $referral_detail = $this->applyReferralAmount($bookings->user_id);
                if($referral_detail['is_applicable'])
                {
                    $referral_data = $referral_detail['referral_data'];                            
                    $referral_amount = $referral_data['referral_amount'];

                    $booking_price = $booking_price - $referral_amount;

                }
                
                $calculate_charges = $this->getTotalChargesWithSkepPercent($booking_price);

                //
                $skep_charge_amount = $calculate_charges['skep_charge_amount'];
                $skep_charge_amount_in_cent = $calculate_charges['skep_charge_amount_in_cent'];
                $total = $calculate_charges['total'];
                $total_in_cent = $calculate_charges['total_in_cent'];
                               
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
                    $charge_params['first_name'] = $user['first_name'] ;
                    $charge_params['email'] = $user['email'] ;
                    $charge_params['job_id'] = $bookings->job_id;
                    
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
                    $updateStripeData = Bookings::SaveChargesStripeData($stripeData, $bookings_id);
                    if ($updateStripeData) 
                    {   
                        /**** Referral data update */
                        Users::where('id', $bookings->user_id)->update(['referral_balance' => $this->amountToFloat($remaining_referral_balance)]);

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
                }
                //

                Log::Info("======================");
                Log::Info("calculate_charges : " . json_encode($calculate_charges));
                Log::Info("======================");
               
            }
        }
        $str = '';
        if ($ids) {
            $str .= 'Homeowners with booking ids ' . implode(", ", $ids) . 'having no customer id';
        }
        if ($ids1) {
            $str .= 'Stripe data is not saved in db for ids ' . implode(", ", $ids);
        }
        if ($ids2) {
            $str .= 'Homeowners with booking ids ' . implode(", ", $ids) . 'not able to process the charge';
        }
        if (empty($ids) && empty($ids1) && empty($ids2)) {
            $str .= 'charge processed successfully.';
        }
        exit($str);
    }

    /*
     * 
     * booking is mark as orphan and not visible to homeowner anymore
     * using in cron
     */
    public function makeBookingToOrphan()
    {
        $current_time = date("Y-m-d H:i:s");
       
        $instant_orphan_time = env("INSTANT_ORPHAN_BOOKING_TIME");
        $advance_orphan_time = env("ADVANCE_ORPHAN_BOOKING_TIME");
        
        $get_orphan_booking_sql = "SELECT bookings.id,bookings.booking_type, bookings.user_id,bookings.service_provider_id, bookings.is_cancelled,bookings.booking_status, users.device_token
        FROM bookings JOIN `users` ON 
        bookings.user_id = users.id WHERE 
        `booking_status` = '0' AND 
        `is_orphan_booking` = 0 AND 
        (is_cancelled = 0 OR (is_cancelled=1 AND cancelled_by='cleaner')) AND 
        (
           ( 
               `booking_type` = 'instant' AND
               `service_provider_id` = '' AND 
               DATE_ADD(bookings.`created_at`, INTERVAL ".$instant_orphan_time." MINUTE ) < '".$current_time."' 
           ) OR
           (
            `booking_type` = 'advanced' AND             
            DATE_ADD(bookings.`created_at`, INTERVAL ".$advance_orphan_time." MINUTE ) < '".$current_time."' 
           )
            
        ) ";
        
        Log::Info(" get_orphan_booking_sql SQL::: > ".$get_orphan_booking_sql);

        $get_booking_data = DB::select($get_orphan_booking_sql);
        $count_values = DB::select($get_orphan_booking_sql);

        $homeowner_tokens = array();
        if ($count_values > 0) 
        {
            foreach ($get_booking_data as $booking) 
            {
                $update_value = DB::table('bookings')
                        ->where('id',$booking->id)                                                
                        ->update(['is_orphan_booking'=>1]);
                
                // this is for to delete all clear notification
                $this->deleteNotification($booking->id);

                if(!empty($booking->device_token))
                {
                    $homeowner_tokens[] = $booking->device_token;
                }
                
                
            }
        }
        Log::Info("homeowner_tokens : ".json_encode($homeowner_tokens));
        
        if(!empty($homeowner_tokens))
        {       
            if($booking->booking_type == 'instant')
                $body = 'Sorry no cleaner is available at the time being. Please try again later or use our Advanced booking feature.';
            else
                $body = 'Sorry no cleaner available please try again.';    
                
            $payload = array();            
            $payload['title'] = 'Booking Not accepted';            
            $payload['body'] = $body;
                            
            $this->send_orphan_notification($homeowner_tokens, $payload);
                        
            

        }
        
                        
        exit;
        
    }

    /*
     * this is used to transfer to all cleaners who earned referral balance
    */
    function transferReferralBalanceToCleaners()
    {
        Log::Info("transferReferralBalanceToCleaners");

        $bookings = new Bookings();
        $cleaner_lists = Users::where('referral_balance', '>' , 0)
                        ->where('status',1)
                        ->where('authenticate_status',1)                       
                        ->where('user_type','cleaner')                       
                        ->get();     
        if($cleaner_lists)
        {
            foreach($cleaner_lists as $cleaner)
            {  
                $user_id = $cleaner['id'];
                $referral_balance = $cleaner['referral_balance'];
                $referral_balance_in_cent = $referral_balance*100;

                $stripeUserDetails = StripeUserDetails::where(['user_id' => $user_id])->first();
                if(!empty($stripeUserDetails) && !empty($stripeUserDetails->account_id))
                {
                    
                    $platform_balance = $this->stripe_platform_balance();
                    
                    Log::Info( "\ncleanerActId: ".$cleanerActId = $stripeUserDetails->account_id);
                    Log::Info( "\n platform_balance : ".$platform_balance);

                    if($platform_balance < env("MINIMUM_PLATFORM_BALANCE"))
                    {
                        break;
                    }
                    if($platform_balance > $referral_balance_in_cent)
                    {
                        $platform_transfer = \Stripe\Transfer::create([
                            "amount" => $referral_balance_in_cent,
                            "currency" => "cad",
                            "destination" => $cleanerActId,
                            "description" =>"Referral earning from SKEP platform account - ".$referral_balance
                            ]);
                        //Log::info("platform_transfer :".json_encode($platform_transfer));    

                        if($platform_transfer)
                        {                           
                            Users::where('id', $user_id)->update(['referral_balance' => 0]);                            
                        }
                    }
                    
                }                                

            }     
        }
    }

    /*
     * this is used to transfer to all cleaners who get paid by using 
     * home owner earned referral balance
     * and didn't transfer to cleaner at markAsComplete time
     * in case of insufficient stripe platform balance      
    */
    function transferBookingReferralBalanceToCleaners()
    {

        $get_booking_sql = "SELECT *
        FROM bookings  WHERE 
        `booking_status` = '1' AND 
        `is_orphan_booking` = 0 AND 
        `is_completed` = 1 AND
        transfer_by_platform_balance > 0 AND
        (platform_transfer_id IS NULL or platform_transfer_id!='' ) ";
        
        Log::Info("TransferBookingReferralBalanceToCleaners SQL::: > ".$get_booking_sql);

        $get_booking_data = DB::select($get_booking_sql);
        $count_values = DB::select($get_booking_sql);

        if($count_values)
        {
            foreach($get_booking_data as $booking)
            {  
                $booking_id = $booking->id;
                $service_provider_id = $booking->service_provider_id;
                $job_id = $booking->job_id;
                
                $transfer_by_platform_balance = $booking->transfer_by_platform_balance;
                $transfer_by_platform_balance_in_cent = $transfer_by_platform_balance*100;

                $stripeUserDetails = StripeUserDetails::where(['user_id' => $service_provider_id])->first();
                if(!empty($stripeUserDetails) && !empty($stripeUserDetails->account_id))
                {
                    
                    $platform_balance = $this->stripe_platform_balance();
                    
                    $cleanerActId = $stripeUserDetails->account_id;
                    
                    Log::Info( "\ncleanerActId: ".$cleanerActId);
                    Log::Info( "\n platform_balance : ".$platform_balance);

                    if($platform_balance < env("MINIMUM_PLATFORM_BALANCE"))
                    {
                        break;
                    }

                    if($platform_balance > $transfer_by_platform_balance_in_cent)
                    {

                        try{

                            $platform_transfer = \Stripe\Transfer::create([
                                "amount" => $transfer_by_platform_balance_in_cent,
                                "currency" => "cad",
                                "destination" => $cleanerActId,
                                "description" =>"Remaining Referral Booking amount from SKEP platform account - ".$transfer_by_platform_balance.' Job ID: '.$job_id
                            ]);

                            $platform_transfer_id = $platform_transfer['id'];
                                
                            Bookings::where('id', $booking_id)->update(['platform_transfer_id' => $platform_transfer_id]);                            
    
                        }
                        catch(\Exception $e){
            
                            Log::Info("CRON PLATFROM Transfer FAILED: BOOKING: ".$booking_id." - ". $e->getMessage());
                            
                        }

                    }
                    
                }                                

            }     
        }
    }
    /*
     * Function used to transfer money to cleaners
     */
    public function transferMoneyToCleaners()
    {

        Log::Info("transferMoneyToCleaners");
        $this->transferReferralBalanceToCleaners();
        $this->transferBookingReferralBalanceToCleaners();
        
                
    }

    public function profileCompleteEmailNotification()
    {
        $check_cleaner_sql = "SELECT * FROM `users` WHERE 
                    `user_type` = 'cleaner' AND 
                    email IS NOT NULL AND 
                    (`is_email_verified` = 0 OR 
                    `selfie_image` IS NULL OR 
                    `government_id_image_front` IS NULL)";
        $check_cleaners = DB::select($check_cleaner_sql);
        
        if (!empty($check_cleaners)) {
            foreach ($check_cleaners as $cleaners) {
                
                $send = Mail::send('emails.profile_complete', ['user' => $cleaners], function ($m) use ($cleaners) {
                    $m->from(env("MAIL_SUPPORT"), env("MAIL_FROM"));
                    $m->bcc(env("MAIL_SUPPORT"), env("MAIL_FROM"));
                    $name = $cleaners->first_name.' '.$cleaners->last_name;                    
                    $m->to($cleaners->email, $name)->subject('SKEP Home: Complete Profile');
                    
                });
                
            
            }
        }
        

    }

    /**
     * Before start service push will send to cleaner
     * push will send 15 mins (time will change from env) before service time
     * 
     */
    public function bookingStartNotification()
    {
        
        $time_in_mins = env("PRE_SERVICE_PUSH_IN_MINS");        
        $current_time = date("Y-m-d H:i:s");                

        $sql = "SELECT B.id,B.service_start_time,U.device_type,U.device_token,U.push_notification,U.timezone FROM bookings B, users U WHERE
            B.`service_provider_id` IS NOT NULL AND
            B.`service_provider_id` != '' AND
            B.`is_cancelled` = 0 AND            
            B.`is_completed` = 0 AND
            B.`is_sent_notification` = 0 AND
            B.service_provider_id=U.id  AND
            FLOOR(TIME_TO_SEC(TIMEDIFF(B.service_start_time,'".$current_time."'))/60) <".$time_in_mins." AND
            FLOOR(TIME_TO_SEC(TIMEDIFF(B.service_start_time,'".$current_time."'))/60) >0
            ";
        Log::Info("CRON: bookingStartNotification SQL: ".$sql);                        
        $getresults = DB::select($sql);
        if ($getresults) {
            
            foreach ($getresults as $bookings) {
                
                $bookings_id = $bookings->id;
                $timezone = $bookings->timezone;
                
                $service_start_time_user = $this->utcTimeToUserTime($bookings->service_start_time, $timezone);
                $booking_time = date("H:i:s", strtotime($service_start_time_user));

                $diff = strtotime($bookings->service_start_time) - strtotime($current_time);
                $diff_in_min = floor($diff/60);

                $payload['title'] = 'Service Start';
                $payload['body'] = "Your booking for today at ".$booking_time." will start in ".$diff_in_min." mins. Make sure to tap on the 'Start job' button in the booking details screen before starting the job.";

                if ($bookings->push_notification == 1) 
                {
                    $this->pre_service_notification($bookings->device_token, $payload);
                    
                }
                $update = ['is_sent_notification' => 1];
                $update_user_location = DB::table('bookings')->where('id', $bookings_id)->update($update);


            }
        }
    }

    /**
     * After service complete push will send to cleaner if its not markAsComplete
     * push will send 15 mins (time will change from env) after service time
     * 
     */
    public function bookingCompleteNotification()
    {
        
        $time_in_mins = env("POST_SERVICE_PUSH_IN_MINS");        
        $current_time = date("Y-m-d H:i:s");                

        $sql = "SELECT B.id,B.service_start_time,B.service_end_time,U.device_type,U.device_token,U.push_notification,U.timezone FROM bookings B, users U WHERE
            B.`service_provider_id` IS NOT NULL AND
            B.`service_provider_id` != '' AND
            B.`is_cancelled` = 0 AND            
            B.`is_completed` = 0 AND
            B.`is_in_progress` = 1 AND            
            B.`is_sent_complete_notification` = 0 AND
            B.service_provider_id=U.id  AND
            FLOOR(TIME_TO_SEC(TIMEDIFF('".$current_time."',B.service_end_time))/60) > ".$time_in_mins;
        Log::Info("CRON: bookingCompleteNotification SQL: ".$sql);                        
        $getresults = DB::select($sql);
        if ($getresults) {
            
            foreach ($getresults as $bookings) {
                
                $bookings_id = $bookings->id;
                $timezone = $bookings->timezone;
                
                $service_end_time_user = $this->utcTimeToUserTime($bookings->service_end_time, $timezone);
                $booking_time = date("H:i:s", strtotime($service_end_time_user));

                $payload['title'] = 'Service Complete';
                $payload['body'] = "Your booking has been completed at ".$booking_time.". Make sure to tap on the 'Complete job' button in the booking details screen to get paid.";

                if ($bookings->push_notification == 1) 
                {
                    $this->pre_service_notification($bookings->device_token, $payload);
                    
                }
                $update = ['is_sent_complete_notification' => 1];
                $update_user_location = DB::table('bookings')->where('id', $bookings_id)->update($update);

            }
        }
    }

    /*
     * Function used to send push before start and after complete the job to cleaners
     */
    public function sendNotification()
    {

        Log::Info("sendNotification");
        $this->bookingStartNotification();
        $this->bookingCompleteNotification();                        
    }


    /**
     * send request to cleaner
     * based on mass blass algorithm
     * fllow @BookingController ->createAdvancedBookings mass blass section
     */
    public function sendMassBlassRequest()
    {
        
        $time_in_mins = env("MASS_BLASS_SEARCH_TIME");        
        $distanceNeedToMatch = env("ADD_ADVANCED_BOOKING_DISTANCE");
        
        $current_time = date("Y-m-d H:i:s");

        $ids = $ids1 = $ids2 = [];

        $current_time = date("Y-m-d H:i:s");
       
        Log::Info("CRON sendMassBlassRequest: ".env("MASS_BLASS_SEARCH_TIME"));
        
        $booking_sql = "SELECT *  FROM `bookings` WHERE 
        `service_provider_id` = '' AND 
        is_cancelled = 0 AND     
        is_orphan_booking = 0 AND 
        booking_type = 'advanced' AND                 
        mass_blast_search != 'gta' AND                 
        DATE_ADD(`created_at`, INTERVAL ".$time_in_mins." MINUTE ) >='".$current_time."' ORDER BY created_at DESC";
 
        Log::Info("sendMassBlassRequest: ".$booking_sql);

        $booking_data = DB::select($booking_sql);
        $booking_counts = count($booking_data);        

        if($booking_counts > 0)
        {
            foreach ($booking_data as $booking) {

                $user_exists = Users::where('id', $booking->user_id)->first();
                $timezone = $user_exists['timezone'];

                $mass_blast_search = $booking->mass_blast_search;

                $input = array();
                if($booking->exclude_previous_cleaner > 0)
                {
                    $input['exclude_previous_cleaner'] = $booking->exclude_previous_cleaner; // for advance fav cleanr idle/reject dont send to this cleaner
                }
                
                if($mass_blast_search == "one_hour_before_after")
                {
                    
                    $input['booking_id'] = $booking->id;
                    $input['mass_blast_search'] = "current_clean_in_area";                                                            
                    
                    $this->getCleanersWithPreviousClean($input,$timezone,$distanceNeedToMatch);                    
                }
                if($mass_blast_search == "current_clean_in_area")
                {
                    $input['booking_id'] = $booking->id;
                    $input['mass_blast_search'] = "preferred_work_area";
                    
                    $this->getCleanersWithinPreferredWorkArea($input,$timezone);                    
                }
                if($mass_blast_search == "preferred_work_area")
                {
                    $input['booking_id'] = $booking->id;
                    $input['mass_blast_search'] = "gta";                    

                    $this->getCleanersGTA($input,$timezone);                    

                }
                
                
            }
        }

    }
    public function sendInstantBookingRequest()
    {
        $time_in_mins = env("INSTANT_ORPHAN_BOOKING_TIME");        
        $max_radius = env("ADD_INSTANT_BOOKING_DISTANCE");        
        $current_time = date("Y-m-d H:i:s");

        $booking_sql = "SELECT *  FROM `bookings` WHERE 
        `service_provider_id` = '' AND 
        is_cancelled = 0 AND     
        is_orphan_booking = 0 AND 
        booking_type = 'instant' AND                 
        DATE_ADD(`created_at`, INTERVAL ".$time_in_mins." MINUTE ) >='".$current_time."' ORDER BY created_at DESC";
 
        Log::Info("sendInstantBookingRequest: ".$booking_sql);

        $booking_data = DB::select($booking_sql);
        $booking_counts = count($booking_data);

        if($booking_counts > 0)
        {
            foreach ($booking_data as $booking) {

                $booking_id = $booking->id;                
                $latitude = $booking->latitude;                
                $longitude = $booking->longitude;                
                
                $user_exists = Users::where('id', $booking->user_id)->first();
                $timezone = $user_exists['timezone'];

                $service_start_time_utc = $booking->service_start_time;
                $service_end_time_utc = $booking->service_end_time;
                $booking_date_utc = date('Y-m-d', strtotime($service_start_time_utc));         

                $booking_address = $booking->booking_address;
                
                //$check_cleaner_sql = "SELECT * FROM `users` WHERE `user_type` = 'cleaner' AND 
                //                    `status` = 1 AND 
                //                    `work_status` = 1 AND                                         
                //                    `account_blocked` = '0' AND 
                //                    FIND_IN_SET('" . $booking->search_work_region . "',work_area) > 0 AND 
                //                    id NOT IN (SELECT receiver_id from notifications WHERE booking_id = '".$booking_id."')";
                
                $exclude_pending_complete_cleaners_sql = $this->get_pending_complete_cleaners_sql();
                $exlude_notifications_sql = "SELECT receiver_id from notifications WHERE booking_id = '".$booking_id."'";
                
                $check_cleaner_sql ="SELECT  *, 
                 ( 3959 * acos 
                     (   cos ( radians(".$latitude.") ) * cos( radians( latitude ) ) * 
                         cos ( radians( longitude ) - radians(".$longitude.") ) + 
                         sin ( radians(".$latitude.") ) * sin( radians( latitude ) ) 
                     ) 
                 ) * 1609.34 AS radius_in_meter 
                 FROM `users` WHERE 
                 `user_type` = 'cleaner' AND 
                 `status` = 1 AND         
                 `work_status` = 1 AND         
                 `account_blocked` = '0'";
                                
                $check_cleaner_sql .= " AND id NOT IN (".$exlude_notifications_sql. " UNION ".$exclude_pending_complete_cleaners_sql.") ";

                $check_cleaner_sql .= " HAVING radius_in_meter <= ".$max_radius;
                $check_cleaner_sql .= " ORDER BY radius_in_meter";
                $check_cleaners = DB::select($check_cleaner_sql);
                
                Log::info("==============\ncheck instant  sql: ".$check_cleaner_sql);

                $user_ids = [];
                                                
                if (!empty($check_cleaners)) {
                    foreach ($check_cleaners as $cleaners) {
                                               
                        $is_busy = $this->isCleanerBusy($cleaners->id,$service_start_time_utc,$service_end_time_utc,'instant');
                        if (!$is_busy) {
                            $user_ids[] = $cleaners;
                        }
                    }   
                }
                Log::Info("==============================");
                Log::Info("user_ids::::".json_encode($user_ids));
                Log::Info("==============================");

                if (!empty($user_ids)) {
                                            
                    $cleaner_tokens =  [];

                    foreach ($user_ids as $user) {
                        
                        $array = ['sender_id' => $booking->user_id, 'receiver_id' => $user->id, 'booking_id' => $booking_id, 'status' => '0'];
                        $notifications = Notifications::create($array);

                        if (!empty($user->device_token)) {
                            $cleaner_tokens[] = $user->device_token;
                        }

                    }
                    Log::Info("cleaner_tokens : ".json_encode($cleaner_tokens));
                                    
                    if(!empty($cleaner_tokens))
                    {                        
                        
                        $payload['title'] = 'New Booking';
                        $payload['body'] = 'You have a new booking at ' . $booking_address;
                        $payload['value'] = $booking_id;
                        $payload['type'] = 'instant';
                        $payload['user_type'] = 'cleaner';

                        /* Send notification to instant booking cleaners */
                        $pushResult = $this->send_instant_notification($cleaner_tokens, $payload);

                        

                    }                        
                    
                }

            }
        }                        
        exit;
        
    }

    /**
     * select cleaner from addadvanceBookings and call to createAdvancedBookings with service_provider_id (fav cleaner)
     * if Favorite cleaner rejects or does not respond within the defined timeframe. 
     * Home owner is messaged “Sorry your favorite cleaner is not available 
     * at that time would you like us to provide you with another cleaner” 
     */
    function sendHomeOwnerFavCleanerNotification()
    {

        $time_in_mins = env("ADVANCE_FAV_CLEANER_WAITING_TIME");        
        
        $current_time = date("Y-m-d H:i:s");               
        $booking_sql = "SELECT bookings.id, bookings.booking_type, 
        bookings.user_id, bookings.service_provider_id, bookings.is_cancelled,
        bookings.booking_status, bookings.service_start_time, bookings.service_end_time,
        bookings.booking_hours, bookings.booking_price, bookings.booking_address,
        users.device_token, users.push_notification, users.timezone,
        cleaners.first_name as cleaner_first_name,cleaners.last_name as cleaner_last_name
        FROM bookings JOIN `users`  ON 
        bookings.user_id = users.id 
        JOIN `users` as  cleaners ON
        bookings.service_provider_id = cleaners.id  WHERE 
        bookings.`service_provider_id` > 0 AND 
        bookings.`booking_type` = 'advanced' AND                 
        bookings.`booking_status` = '0' AND 
        bookings.`advance_fav_cleaner_notify` = 0 AND 
        `is_orphan_booking` = 0 AND  
        bookings.`is_cancelled` = 0 AND         
        DATE_ADD(bookings.`created_at`, INTERVAL ".$time_in_mins." MINUTE ) < '".$current_time."' ORDER BY bookings.created_at DESC";
 
        Log::Info("sendHomeOwnerFavCleanerNotification: ".$booking_sql);

        $booking_data = DB::select($booking_sql);
        $booking_counts = count($booking_data);

        if($booking_counts > 0)
        {
            foreach ($booking_data as $booking) {
                
                $user_id = $booking->user_id;
                $service_provider_id = $booking->service_provider_id;
                $cleaner_name = $booking->cleaner_first_name. ' '.$booking->cleaner_last_name;

                
                $service_start_time_user = $this->utcTimeToUserTime($booking->service_start_time, $booking->timezone);
                $service_end_time_user = $this->utcTimeToUserTime($booking->service_end_time, $booking->timezone);

                $where = ['booking_id' => $booking->id, 'receiver_id' => $service_provider_id];                    
                DB::table('notifications')->where($where)->delete();

                $where = ['service_provider_id' => $service_provider_id, 'user_id' => $user_id, 'status' => 'favourite'];
                $check_favourite = Favourites::where($where)->first();
                if (!empty($check_favourite)) 
                {
                    $body = "Sorry your favorite cleaner ".$cleaner_name." is not available on ".date('Y-m-d',strtotime($service_start_time_user))." at ".date('h:i A',strtotime($service_start_time_user)).". Would you like us to provide you with another cleaner?";
                }
                else
                {
                    $body = "Sorry the selected cleaner is not available at that time would you like us to provide you with another cleaner?";                    
                }
                                
                $advance_fav_cleaner_notify = 1;          
                $payload = array();
                $payload['title'] = 'Booking Not accepted';
                $payload['body'] = $body;
                $payload['type'] = 'advance_fav_cleaner_notify';
                $payload['user_type'] = 'homeOwner';                
                
                $notification_data= [];
                $notification_data['type'] = 'advance_fav_cleaner_notify';
                $notification_data['user_type'] = 'homeOwner';
                $notification_data['booking_id'] = $booking->id;
                
                $payload['notification_data'] = $notification_data;
                
                Bookings::SaveChargesStripeData(
                    [
                        'is_in_progress' => 0,
                        'is_on_route' => 0,                        
                        'advance_fav_cleaner_notify' => $advance_fav_cleaner_notify
                    ], $booking->id);   

                if(!empty($booking->device_token) && $booking->push_notification == 1)
                {                                                                               
                    $notify_user = $this->send_cancel_notification($booking->device_token, $payload);                                        
                } 
                
                // no need to send push in notification bell icon                    
                            

            }
        }

    }
}
