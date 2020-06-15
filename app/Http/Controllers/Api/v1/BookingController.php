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
use App\Models\api\v1\UserReferralHistories;
//use App\Models\api\v1\Jsons;

class BookingController extends Controller
{
    public $succesStatus = 200;
    public $unauthorizedStatus = 401;
    public $userType = "";
    public $apiToken;

    /* Constructor function to get userid, usertype and authrization token in header */

    public function __construct(Request $request)
    {
        /*** @auther: ALOK => START ***/
        /* $input_data = $request->input();
        $currentPath= Route::getFacadeRoot()->current()->uri();
        $array = ['action' => $currentPath, 'data' => json_encode($input_data), 'call_type' => 'request'];
        $create_json = Jsons::create($array);
         */
        /*** @auther: ALOK => END ***/

        // Unique Token
        $this->apiToken = uniqid(base64_encode(str_random(20)));
        $this->user_type = $request->header('userType') ? $request->header('userType') : "";
        $this->userId = $request->header('userId') ? $request->header('userId') : "";
        //$this->userId = 345;
        $this->skep_percent = env("SKEP_CHARGES_PERCENT");
        $this->hourly_rate = env("HOURLY_RATE_FOR_SERVICES");
        $this->charge_deduct_cleaner_percent = env("CLEANER_CHARGE_DEDUCTION_PERCENT");
        $this->stripe_fees = env("STRIPE_FEES");

        $this->charge_deduct_cleaner_total_percent = $this->charge_deduct_cleaner_percent;
        $this->homeowner_penalty_percent = env("HOMEOWNER_PENALTY");
        $this->cancelled_hrs = env("CANCELLATION_HRS_INS");
        $this->cancelled_hrs_adv = env("CANCELLATION_HRS_ADV");
        $this->add_advanced_booking_distance = env("ADD_ADVANCED_BOOKING_DISTANCE");
        $this->add_advanced_booking_distance_gta = env("ADD_ADVANCED_BOOKING_DISTANCE_GTA");
        
        $this->cleaner_ratings = env("CLEANER_RATING");

        $this->add_instant_booking_distance = env("ADD_INSTANT_BOOKING_DISTANCE");
        

       
    }


    function testTime(Request $request)
    {
        
        $where = ['id' => 52];
        $check_booking_exists = Bookings::where($where)->first();
        
        
        $homeOwnerCharges = $check_booking_exists['amount_paid'];
        $homeOwnerChargesInCent = $homeOwnerCharges*100;
        $chargeId = $check_booking_exists['charge_id'];                


        $owner_details = Users::where('id', $check_booking_exists['user_id'])->first();
        $stripeUserDetails = StripeUserDetails::where(['user_id' => $check_booking_exists['user_id']])->first();
                        
        $cusId = $stripeUserDetails->customer_id;        
        $charge_params = array();
        $charge_params['charge_id'] = $chargeId;
        $charge_params['total_in_cent'] = $homeOwnerChargesInCent;
        $charge_params['cusId'] = $cusId;
        $charge_params['first_name'] = $owner_details['first_name'] ;
        $charge_params['email'] = $owner_details['email'] ;
        $charge_params['job_id'] = $check_booking_exists['job_id'];                
        $charge_params['booking_id'] = $check_booking_exists['id'];                
        $charge = $this->captureStripeCharge($charge_params);
        
                
        
        exit;

        
    }
    /** Create unique job id for bookings **/
    public function generateUniqueJobID($length = 10)
    {

        $random = "";
        $data = "";
        srand((double) microtime() * 1000000);

        $data = "9876549876542156012";
        $data .= "0123456789";

        for ($i = 0; $i < $length; $i++) {
            if ($i > 9) {
                break;
            } else {
                $random .= substr($data, (rand() % (strlen($data))), 1);
            }
        }
        return $random;

    }

    /* Api function to create instant bookings */

    public function addInstantBookings(Request $request)
    {

        $input = $request->all();
        $distanceNeedToMatch = $this->add_instant_booking_distance;        
        
        Log::info('addInstantBookings: ' . json_encode($input));
        
        $this->validation(
            $request->all(),
            [
                'booking_date' => 'required',
                'space_id' => 'required',
                'service_start_time' => 'required',
                'service_end_time' => 'required',
                'booking_price' => 'required',
                'booking_type' => 'required',
                'booking_address' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'booking_hours' => 'required',
                'area_of_region' => 'required',

            ]
        );

        
        //$input['service_start_time'] = "2019-12-19 17:46:51";
        //$input['service_end_time'] = "2019-12-19 20:46:51";

        /* Check User exists in users table or not */
        $check_user_exists = Users::where('id', $this->userId)->first();

        Log::Info("check_user_exists:" . $this->userId . "++++++++" . json_encode($check_user_exists));

        if (!empty($check_user_exists)) {

            
            if($check_user_exists['status'] == 1)
            {
                $timezone = $check_user_exists['timezone'];

                $service_start_time_user = $input['service_start_time'];
                $service_end_time_user = $input['service_end_time'];
                $booking_date_user = $input['booking_date'];

                $this->checkValidBookingTime($service_start_time_user,$service_end_time_user);
                
                $service_start_time_utc = $this->userTimeToUTCTime($service_start_time_user, $timezone);
                $service_end_time_utc = $this->userTimeToUTCTime($service_end_time_user, $timezone);
                $booking_date_utc = date('Y-m-d', strtotime($service_start_time_utc));

                /* check if booking exists in same time and date for a space */
                $home_owner_booking_exists = $this->isHomeOwnerBookingExists($this->userId,$input['space_id'],$service_start_time_utc,$service_end_time_utc);
                if($home_owner_booking_exists)
                {
                    $this->error("You currently have an existing booking for this time. Please select another time to schedule.");
                }
                
                $input['service_start_time'] = $service_start_time_utc;
                $input['service_end_time'] = $service_end_time_utc;
                $input['booking_date'] = $booking_date_utc;


                Log::info('=======================\ntimezone: ' . $timezone);
                Log::info('booking_date_user: ' . $booking_date_user);
                Log::info('booking_date_utc: ' . $booking_date_utc);
                Log::info('service_start_time_user: ' . $service_start_time_user);
                Log::info('service_start_time_utc: ' . $service_start_time_utc);
                Log::info('=======================\n ');

                $services = (!empty($input['booking_services'])) ? $input['booking_services'] : "";

                $latitude = $input['latitude'];
                $longitude = $input['longitude'];

                /* Get all cleaners near user location */

                //$check_cleaner_sql = "SELECT * FROM `users` WHERE `user_type` = 'cleaner' AND 
                //                        `status` = 1 AND 
                //                        `work_status` = 1 AND                                         
                //                        `account_blocked` = '0'";
                
                $exclude_pending_complete_cleaners_sql = $this->get_pending_complete_cleaners_sql();

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
                    `account_blocked` = '0' AND
                    id NOT IN (".$exclude_pending_complete_cleaners_sql.")                    
                    ";
                    
                $check_cleaner_sql .= " HAVING radius_in_meter <= ".$distanceNeedToMatch;
                $check_cleaner_sql .= " ORDER BY radius_in_meter";
                
                $check_cleaners = DB::select($check_cleaner_sql);
                
                Log::info("==============\ncheck instant  sql: ".$check_cleaner_sql);

                $user_ids = [];
                                
                if (!empty($check_cleaners)) {
                    
                    foreach ($check_cleaners as $cleaners) {

                        $is_busy = $this->isCleanerBusy($cleaners->id,$service_start_time_utc,$service_end_time_utc,'instant');
                        if (!$is_busy) {
                            
                            //$latlong = ['cleaner_lat' => $cleaners->latitude, 'cleaner_long' => $cleaners->longitude, 'owner_lat' => $input['latitude'], 'owner_long' => $input['longitude']];
                            //$distanceTime = $this->getDistanceDiffMins($latlong);

                            //Log::info("distanceTime:".$cleaners->id.'('.$cleaners->email.') :: '. json_encode($distanceTime));

                            //$distance = $distanceTime['distance'];
                            //$time = $distanceTime['time']; 
                                                
                            //if ($distance <= $distanceNeedToMatch) {                            
                            //        $user_ids[] = $cleaners;                                
                            //}
                            $user_ids[] = $cleaners;
                        }
                                                
                        
                    }
                                    
                    Log::info('addInstantBookings USERS: ' . json_encode($user_ids));
                    if (!empty($user_ids)) {
                        
                        $instruction = (!empty($input['special_instructions'])) ? $input['special_instructions'] : "";                        
                       

                        $booking_id = $this->createBooking($input,$timezone);      
                        
                        $cleaner_tokens =  [];

                        foreach ($user_ids as $user) {
                            $array = ['sender_id' => $this->userId, 'receiver_id' => $user->id, 'booking_id' => $booking_id, 'status' => '0'];
                            $notifications = Notifications::create($array);

                            if (!empty($user->device_token)) {
                                $cleaner_tokens[] = $user->device_token;
                            }

                        }

                        Log::Info("cleaner_tokens : ".json_encode($cleaner_tokens));

                        if(!empty($cleaner_tokens))
                        {                        
                            $time = date("H:i", strtotime($service_start_time_user));
                            $end_time = date("H:i", strtotime($service_end_time_user));
                            $payload['title'] = 'New Booking';
                            $payload['body'] = 'You have a new booking at ' . $input['booking_address'];
                            $payload['value'] = $booking_id;
                            $payload['type'] = 'instant';
                            $payload['user_type'] = 'cleaner';

                            /* Send notification to instant booking cleaners */
                            $pushResult = $this->send_instant_notification($cleaner_tokens, $payload);

                            if($pushResult)
                            {                                
                                $this->success("Cleaners have been notified!\nWe are matching a cleaner with your request! We will notify you shortly!", $booking_id);
                            }
                            else
                            {
                                $this->error("Sorry no cleaner is available at the time being. Please try again later or use our Advanced booking feature", ['err' => 2]);                                    
                            }
                            

                        }                        
                        else
                        {
                            $this->error("Sorry no cleaner is available at the time being. Please try again later or use our Advanced booking feature", ['err' => 2]);    

                        }
                        
                    } else {
                        $this->error("Sorry no cleaner is available at the time being. Please try again later or use our Advanced booking feature", ['err' => 2]);
                    }

                    
                } else {
                    $this->error("Sorry no cleaner is available at the time being. Please try again later or use our Advanced booking feature", ['err' => 4]);
                }

            }
            else
            {
                if($check_user_exists['authenticate_status'] == 1)
                {
                    $this->error("Sorry, you can't make this booking. We are reviewing your account & will notify you soon! Thank you for your patience!", ['err' => 1]);
                }
                else
                {
                    $this->error("Please complete your account verification process under Account Verification section in the app.", ['err' => 1]);
                }
            }
            
        } else {
            $this->error("User does not exist.", ['err' => 0]);
        }
    }

    /* Api function to add a cleaner to favourite list */

    public function addCleanerToFavourite(Request $request)
    {
        $input = $request->all();

        $this->validation(
            $request->all(),
            [
                'service_provider_id' => 'required',
            ]
        );
        $check_user_exists = Users::where('id', $this->userId)->first();
        /* Check user exists */
        if (!empty($check_user_exists)) {
            $where = array('user_id' => $this->userId, 'service_provider_id' => $request->input('service_provider_id'));
            $check_favourite_exists = Favourites::where($where)->first();

            /* Mark cleaner as favourite */

            if (!empty($check_favourite_exists)) {
                if ($check_favourite_exists->status == 'favourite') {
                    $check_favourite_exists->status = 'unfavourite';
                    $check_favourite_exists->save();
                    $this->success('Favorite cleaner removed', ['success' => 3]);

                    /* Mark cleaner as unfavourite */

                } else if ($check_favourite_exists->status == 'unfavourite') {
                    $check_favourite_exists->status = 'favourite';
                    $check_favourite_exists->save();
                    $this->success('Favorite cleaner added', ['success' => 2]);
                }

                /* Mark cleaner as favourite */

            } else {
                $array = ['user_id' => $this->userId, 'service_provider_id' => $request->input('service_provider_id'), 'status' => 'favourite'];
                $favourite = Favourites::create($array);
                if (!empty($favourite['id'])) {
                    $this->success('Favorite cleaner added', ['success' => 1]);
                }
            }
        } else {
            $this->error('Home Owner not found!', ['err' => 0]);
        }
    }

    /* Api function to give ratings and reviews to a cleaner */

    public function addRatingsandReview(Request $request)
    {
        $input = $request->all();

        $this->validation(
            $request->all(),
            [
                'ratings_for' => 'required',
                'ratings' => 'required',
                'booking_id' => 'required',
            ]
        );
       
        $check_users_cleaning = Bookings::where('id', $input['booking_id'])->first();

        if(!empty($check_users_cleaning))
        {
            $check_user_exists = Users::where('id', $this->userId)->first();
            if($check_user_exists['user_type'] == 'homeOwner')
            {
                if($check_users_cleaning['user_id'] != $this->userId)
                {
                    $this->error("You can't give rating to cleaner");
                }    
            }
            else
            {
                if($check_users_cleaning['service_provider_id'] != $this->userId)
                {
                    $this->error("You can't give rating to homeowner");
                }    
            }
            
            if($check_users_cleaning['is_completed'] == 1)
            {
                if($check_user_exists['user_type'] == 'homeOwner')
                {
                    if($check_users_cleaning['ratingGivenByHomeOwner'] == 1)
                    {
                        $this->error("Rating already given for this booking");
                    }    
                }
                if($check_user_exists['user_type'] == 'cleaner')
                {
                    if($check_users_cleaning['ratingGivenByCleaner'] == 1)
                    {
                        $this->error("Rating already given for this booking");
                    }    
                }
                
                $receiver_user_details = Users::where('id', $request['ratings_for'])->first();
                $ratings_val = $receiver_user_details['rating'];

                $array = ['ratings_by' => $this->userId, 
                        'ratings_for' => $request['ratings_for'], 
                        'ratings' => $input['ratings'], 
                        'booking_id' => $input['booking_id']
                        ];

                if (!empty($request['reviews'])) 
                {
                    $array['reviews'] = $request['reviews'];
                } 
                                
                $ratingSum = 0;
                $values = Ratings::create($array);
                if ($values) {
                    if($check_user_exists['user_type'] == 'homeOwner')
                    {
                        $check_users_cleaning->ratingGivenByHomeOwner = 1;
                    }
                    else{
                        $check_users_cleaning->ratingGivenByCleaner = 1;
                    }
                    
                    $check_users_cleaning->save();
        
                    if ($receiver_user_details['push_notification'] == 1) 
                    {
                        $payload = array();
                        $payload['title'] = 'Earned Rating';
                        $payload['body'] = 'You have got a new rating of '.$input['ratings'];
                        $payload['type'] = 'rating';
                        $payload['user_type'] = $check_user_exists['user_type'];
                
                        $this->send_cleaner_rating_notification($receiver_user_details['device_token'], $payload);
                    }
                    
                    $ratings = Ratings::where('ratings_for', $request['ratings_for'])->orderBy('created_at', 'DESC')->limit(10)->get();
                    
                    if (!empty($ratings)) {

                        if(count($ratings) == 10)
                        {
                            foreach ($ratings as $rate) {             
                                $ratingSum += $rate['ratings'];
                            }                
                            $ratings_val = ($ratingSum / count($ratings));                                
                            $ratings_val = $this->ratingFormat($ratings_val);                
                            
                            if ($receiver_user_details) {
                                $receiver_user_details->rating = $ratings_val;
                                $receiver_user_details->save();
                            }
                        }
                        
        
                    }
                                                
                }
            
                    $this->success("Ratings Added Successfully", ['ratings' => $ratings_val]);
                    
                
                
            }            
            else
            {
                $this->error("Booking is not completed. You can not give rating");
            }                        
            
        }
        else {
           $this->error("Booking does not exists");
        }
        
        //if ($value >= 10) {
        
        // } else {
        //   $this->error("Cleaner not eligible for Ratings",['err'=>0]);
        //}
    }

    /* Api function to Check a cleaner is a supercleaner or not */

    public function checkSuperCleaner()
    {

        $current_date = date("Y-m-d H:i:s");
        $last_date = date("t", strtotime($current_date));
        $today_date = date("d", strtotime($current_date));

        /* Check current date is equal to last date of month */

        if ($today_date == $today_date) {
            $where = ['user_type' => 'cleaner', 'account_blocked' => 0];

            /* Get the cleaners whose account is not blocked */

            $cleaners_vals = Users::where($where)->get();
            $cleaners_ids = array();
            $where_condition = ['user_type' => 'cleaner', 'is_super_cleaner' => 1, 'account_blocked' => 0];
            $check_previous_super_cleaners = Users::where($where_condition)->get();
            foreach ($check_previous_super_cleaners as $cleaners) {
                $cleaners_ids[] = $cleaners['id'];

            }
            $super_cleaners = $this->check_supercleaner_active($cleaners_vals);
            if (!empty($cleaners_ids)) {
                $values_here = array_diff($cleaners_ids, $super_cleaners);
            }

            foreach ($values_here as $cleaners_here) {
                $user_details = Users::where('id', $cleaners_here)->first();
                $device_token = $user_details['device_token'];
                $notification['title'] = 'Loose Supercleaner Status';
                $notification['body'] = ' Due to your low rating, You have lost your Super Cleaner Status!';
                $this->send_notification($cleaners_here, $device_token, $notification);
            }

            if (!empty($super_cleaners)) {
                foreach ($intersect as $users) {
                    $where = ['id' => $users, 'is_super_cleaner' => 1];
                    $check_user_exists = Users::where($where)->first();

                    /* Check whether cleaner is super cleaner then send notification to cleaner*/

                    if (!empty($check_user_exists)) {
                        $device_token = $check_user_exists['device_token'];
                        $notification['title'] = 'Super Cleaner Status';
                        $notification['body'] = 'Congratulations!! Your Super Cleaner Status is still Active!';
                        $this->send_notification($users, $device_token, $notification);

                        /* Update cleaner status as super cleaner and send notification */

                    } else {
                        $value = Users::where('id', $users)->update(array('is_super_cleaner' => 1));
                        if ($value == 1) {
                            $device_token = $check_user_exists['device_token'];
                            $notification['title'] = 'Super Cleaner Status';
                            $notification['body'] = 'Congratulations!! You are now a Super Cleaner!';
                            $this->send_notification($users, $device_token, $notification);
                        }
                    }
                }
            }
        }
    }

    /* Function to check all criterias for supercleaners */

    public function check_supercleaner_active($cleaners = '')
    {
        if (!empty($cleaners)) {
            foreach ($cleaners as $cleaner) {
                $cleaner_id = $cleaner['id'];

                /* Get last ten ratings and reviews of cleaner */

                $check_last_ten_reviews = Ratings::where('ratings_for', $cleaner_id)->take(10)->get()->toArray();
                $reviews_to_test = array_column($check_last_ten_reviews, 'ratings');
                $values_here = array_sum($reviews_to_test);

                /* Check whether cleaner last 10 ratings are greater than equal to 4.8 */

                if ($values_here >= 47.9) {
                    $id[] = $cleaner['id'];
                }
                $current_month_start_date = date("Y-m-01");
                $current_month_end_date = date("Y-m-t");

                /* Check cleaner has 25 or more cleans in a month or not */

                $check_bookings_exists = DB::select("SELECT * FROM `bookings` WHERE (date(`created_at`) >= " . $current_month_start_date . " OR date(`created_at`) <= " . $current_month_end_date . ") AND `service_provider_id` = " . $cleaner_id);

                $check_bookings_count = count($check_bookings_exists);

                /* Check successfull clean count is equal to and greater than 25 */

                if ($check_bookings_count >= 1) {
                    $get_id[] = $cleaner_id;
                }

                /* Check cleaner cancel any clean or not */

                $where = ['is_cancelled' => 1, 'cancelled_by' => 'cleaner', 'service_provider_id' => $cleaner_id];
                $check_any_cancellation = Bookings::where($where)->get();
                $check_cancel_count = count($check_any_cancellation);
                if ($check_cancel_count == 0) {
                    $cleaner_id_val[] = $cleaner_id;
                }

                /* Check cleaner did any instant booking cleaning in a month */

                $check_bookings_exists = DB::select("SELECT * FROM `bookings` WHERE (date(`created_at`) >= " . $current_month_start_date . " OR date(`created_at`) <= " . $current_month_end_date . ") AND `service_provider_id` = " . $cleaner_id . " AND `booking_type` = 'instant'");
                $check_instant_booking_count = count($check_bookings_exists);
                if ($check_instant_booking_count >= 1) {
                    $cleaner_id_value[] = $cleaner_id;
                }
            }
        }

        /* Check cleaners that meet all criterias of super cleaner */

        $intersect = array_intersect($id, $get_id, $get_id, $cleaner_id_value);
        return $intersect;
    }

    /* Function to send notification to non-supercleaner about their ratings */

    public function non_supercleaners_ratings($cleaner_id = '', $message = '', $ratings = '')
    {
        if (!empty($cleaner_id)) {
            $notification = [];
            $where = ['id' => $cleaner_id, 'user_type' => 'cleaner', 'is_super_cleaner' => 0];
            $check_cleaner_exists = Users::where($where)->first();
            if (!empty($check_cleaner_exists)) {
                $device_token = $check_cleaner_exists['device_token'];
                $current_date = date("Y-m-d H:i:s");
                $blocked_date = date('Y-m-d H:i:s', strtotime("+3 months", strtotime($current_date)));
                $value = $this->send_notification($cleaner_id, $device_token, $message);
                if ($ratings == 'ratings_less_than_4.3') {
                    if (!empty($value)) {
                        $update_value = ['account_blocked' => 1, 'account_blocked_start_date' => $current_date, 'account_blocked_end_date' => $blocked_date];
                        $values = DB::table('users')->where('id', $cleaner_id)->update($update_value);
                    }
                }
            }
        }
    }

    /* Function to send notification to supercleaner about their ratings */

    public function supercleaners_ratings($cleaner_id = '', $message = '', $ratings = '')
    {
        if (!empty($cleaner_id)) {
            $notification = [];
            $where = ['id' => $cleaner_id, 'user_type' => 'cleaner', 'is_super_cleaner' => 0];
            $check_cleaner_exists = Users::where($where)->first();
            if (!empty($check_cleaner_exists)) {
                $device_token = $check_cleaner_exists['device_token'];
                $current_date = date("Y-m-d H:i:s");
                $blocked_date = date('Y-m-d H:i:s', strtotime("+3 months", strtotime($current_date)));
                $value = $this->send_notification($cleaner_id, $device_token, $message);
                if ($ratings == 'ratings_less_than_4.3') {
                    if (!empty($value)) {
                        $update_value = ['account_blocked' => 1, 'account_blocked_start_date' => $current_date, 'account_blocked_end_date' => $blocked_date];
                        $values = DB::table('users')->where('id', $cleaner_id)->update($update_value);
                    }
                }
            }
        }
    }

    public function getFavCleaners($userId)
    {

        $is_fav_cleaner = 0;        
        $service_cleaner_count = 0;            
                       
        $sql = "SELECT users.*, favourites.status FROM `users` LEFT JOIN `favourites` ON 
        users.id = favourites.service_provider_id WHERE                 
        users.user_type='cleaner' AND 
        users.status = 1 AND 
        users.`account_blocked` = '0' AND                
        favourites.user_id=" . $userId . " AND 
        favourites.status='favourite' ";
        
        $check_favourite_cleaner = DB::select($sql);

        $favourite_count = count($check_favourite_cleaner);

        Log::Info("favourite_count: " . $favourite_count);
        /* 
        condition 1: get cleaners who gave service already to the requested homeowner 
        */
        if ($favourite_count == 0) {
            
            $cleaner_sql = "SELECT  DISTINCT(users.id) as id, users.is_super_cleaner FROM `users`, bookings WHERE 
            users.`user_type` = 'cleaner' AND 
            users.`status` = '1' AND 
            users.`account_blocked` = '0' AND 
            users.id = bookings.service_provider_id AND 
            bookings.user_id='".$userId."' AND 
            bookings.is_completed = 1 ";                
            $get_all_cleaners = DB::select($cleaner_sql);
            $service_cleaner_count = count($get_all_cleaners);
            Log::Info("service_cleaner_count: " . $service_cleaner_count);
            
        }
        if($favourite_count > 0 || $service_cleaner_count > 0)
        {
            $is_fav_cleaner = 1;
        }
        return $is_fav_cleaner;
                 
    }
    /* Function to get favourite cleaners of a user

    Cleaner Search Logic:  Only cleaners with open schedules for that timeslot can be receive the requests below.
    Advance:
    1. Check if account is Verified
    a. If not stop and a message "You need to complete your account verification"
    
     */

    
    public function addadvanceBookings(Request $request)
    {

        $input = $request->all();
        $is_cleaners_found = 0;
        $total_cleaner_found = 0;
        $detail_values = [];
        
        $this->validation(
            $request->all(),
            [
                'booking_date' => 'required',
                'space_id' => 'required',
                'service_start_time' => 'required',
                'service_end_time' => 'required',
                'booking_hours' => 'required',
                'booking_price' => 'required',
                'booking_type' => 'required',
                'booking_frequency' => 'required',
                'booking_address' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'area_of_region' => 'required',
            ]
        );
        Log::Info("addadvanceBookings PARAM:" . json_encode($input));
              
        /* Check User Exists or Not */
        $check_user_exists = Users::where('id', $this->userId)->first();

        $final_selected_cleaners = array();
        if (!empty($check_user_exists)) {

            $service_start_time_user = $input['service_start_time'];
            $service_end_time_user = $input['service_end_time'];
            $booking_date_user = $input['booking_date'];

            $this->checkValidBookingTime($service_start_time_user,$service_end_time_user);

            $timezone = $check_user_exists['timezone'];

            $service_start_time_utc = $this->userTimeToUTCTime($service_start_time_user, $timezone);
            $service_end_time_utc = $this->userTimeToUTCTime($service_end_time_user, $timezone);
            $booking_date_utc = date('Y-m-d', strtotime($service_start_time_utc));

            if ($check_user_exists['status'] == 1) {

                $input['user_id'] = $this->userId;

                    
                $sql = "SELECT users.*, favourites.status FROM `users` LEFT JOIN `favourites` ON 
                users.id = favourites.service_provider_id WHERE                 
                users.user_type='cleaner' AND 
                users.status = 1 AND 
                users.`account_blocked` = '0' AND                
                favourites.user_id=" . $this->userId . " AND 
                favourites.status='favourite'";
                
                $check_favourite_cleaner = DB::select($sql);

                $favourite_count = count($check_favourite_cleaner);

                Log::Info("favourite_count: " . $favourite_count);
                Log::Info("SQL: " . $sql);

                Log::Info("total_cleaner_found: " . $total_cleaner_found);

                /* 
                condition 1: get cleaners who are favorites and
                condition 2: free buffer start time  hrs before and buffer end time the requested service time                
                 */
                if ($favourite_count > 0) {

                    /* Check a cleaner has less than 5 favourite cleaner */
                    $list_of_cleaners = $cleaners_id =  array();
                    foreach ($check_favourite_cleaner as $cleaners) {
                        $cleaners_id[] = $cleaners->id;                        
                    }

                    $i = 0;

                    $cleaners_value = array_unique($cleaners_id);

                    /* Get the list and details of all favouite cleaners of user */

                    Log::Info("====================================");
                    Log::Info(" ALL USERS: " . json_encode($cleaners_value));
                    Log::Info("====================================");

                    if (!empty($cleaners_value)) {

                       // $is_cleaners_found = 1;
                        foreach ($cleaners_value as $cleaners) {
                            $users_details = Users::where('id', $cleaners)->first();
                            
                            if (!empty($users_details['is_supercleaner']) && $users_details['is_supercleaner'] == 1) {
                                $cleaner_status = 1;
                            } else {
                                $cleaner_status = 0;
                            }

                             
                            //push to an array to get all final cleaners and avoid them in next filter
                            array_push($final_selected_cleaners, $users_details['id']);
                            /* Detail array of cleaners */
                            $detail_values[] = [
                                'user_id' => $users_details['id'],
                                'first_name' => ($users_details['first_name']) ? (string) @$users_details['first_name'] : "",
                                'last_name' => ($users_details['last_name']) ? (string) @$users_details['last_name'] : "",
                                'phone_number' => ($users_details['phone_number']) ? (string) @$users_details['phone_number'] : "",
                                'gender' => ($users_details['gender']) ? (string) @$users_details['gender'] : "",
                                'image' => (!empty(@$users_details['image'])) ? $this->get_userimage($detail_values['image']) : "",
                                'is_favourite' => 1,
                                'is_super_cleaner' => ($cleaner_status) ? $cleaner_status : "",
                                'is_cleaner_busy' => 0,
                                'ratings' => (string) $this->ratingFormat(@$users_details->rating),
                            ];
                            $i++;
                            $total_cleaner_found++;

                            /* Break loop when the number of cleaners greater than 5 */
                            if ($total_cleaner_found == 5) {
                                break;
                            }

                        }
                        Log::Info("addadvanceBookings OUPUT STEP 1 : " . json_encode($detail_values));

                    }

                    /* User has no favouite cleaner */

                }

                Log::Info("total_cleaner_found 2: " . $total_cleaner_found);
                Log::Info("addadvanceBookings OUPUT STEP 1 : " . json_encode($detail_values));
                /* 
                condition 1: get cleaners who gave service already to the requested homeowner 
                condition 2: free buffer start time  hrs before and buffer end time the requested service time                
                 */
                if ($total_cleaner_found < 5) {
                    
                    $cleaner_sql = "SELECT  DISTINCT(users.id) as id, users.is_super_cleaner FROM `users`, bookings WHERE 
                    users.`user_type` = 'cleaner' AND 
                    users.`status` = '1' AND 
                    users.`account_blocked` = '0' AND 
                    users.id = bookings.service_provider_id AND 
                    bookings.user_id='".$this->userId."' AND 
                    bookings.is_completed = 1 ";

                    if(!empty($final_selected_cleaners))
                    {
                        $final_selected_cleaner_str = implode(',',$final_selected_cleaners);   
                        $cleaner_sql .=  " AND users.id NOT IN (".$final_selected_cleaner_str.") ";
                    }
                    $cleaner_sql .=  " ORDER BY users.`is_super_cleaner` DESC ";
                    $get_all_cleaners = DB::select($cleaner_sql);

                    Log::Info("cleaner_sql: " . $cleaner_sql);                    

                    if (!empty($get_all_cleaners)) {
                        $cleaners_ids = [];

                        foreach ($get_all_cleaners as $cleaner) {

                            /* Check cleaner as supercleaner */
                            if ($cleaner->is_super_cleaner == 1) {
                                $super_cleaners_ids[] = $cleaner->id;
                            } else {
                                $cleaners_ids[] = $cleaner->id;
                            }
                                                        
                        }
                        if (!empty($super_cleaners_ids)) {
                            $cleaners_id_details = array_merge($super_cleaners_ids, $cleaners_ids);
                        } else {
                            $cleaners_id_details = $cleaners_ids;
                        }
                        $i = 0;
                        foreach ($cleaners_id_details as $cleaners) 
                        {
                            $date = date("Y-m-d");
                            $start_date = date("Y-m-01", strtotime($date));
                            $month_end_date = date("Y-m-t", strtotime($date));
                            $cleaners_details = Users::where('id', $cleaners)->first();
                            
                            $cleaners_vals = [
                                'id' => $cleaners_details['id'],
                                'ratings' => $cleaners_details['rating'],
                            ];

                            $cleaners_ids_values[] = $cleaners_vals;

                        }

                        if (!empty($cleaners_ids_values)) {
                            $ids_values = [];
                            foreach ($cleaners_ids_values as $key => $values) {
                                $ids_values[$key] = $values;
                            }
                            array_multisort($ids_values, SORT_DESC, $cleaners_ids_values);

                            /* Check cleaner ratings is greater than 4.5 */
                            foreach ($ids_values as $values) {
                                //if ($values['ratings'] >= $this->cleaner_ratings) {
                                    $cleaner_id[] = $values['id'];
                                //}
                            }

                            $i = 0;
                            if(isset($cleaner_id) && !empty($cleaner_id))
                            {
                                foreach ($cleaner_id as $id) {
                                    $cleaners_details = Users::where('id', $id)->first();
                                    $where = ['user_id' => $this->userId, 'service_provider_id' => $id, 'status' => 'favourite'];
                                    $check_favourite = Favourites::where($where)->first();
                                    if (!empty($check_favourite)) {
                                        $favourite = 1;
                                    } else {
                                        $favourite = 0;
                                    }

                                    if (!empty($cleaners_details['is_supercleaner'])) {
                                        $cleaner_status = 0;
                                    } else if ($cleaners_details['is_supercleaner'] == 1) {
                                        $cleaner_status = 1;
                                    } else {
                                        $cleaner_status = 0;
                                    }

                                    // Check if cleaner is busy or not 
                                    
                                    $cleaner_busy = 0;
                                    

                                    if (!in_array($cleaners_details['id'], $final_selected_cleaners)) {
                                        //push to an array to get all final cleaners and avoid them in next filter

                                        array_push($final_selected_cleaners, $cleaners_details['id']);

                                        $detail_values[] = [
                                            'user_id' => $cleaners_details['id'],
                                            'first_name' => ($cleaners_details['first_name']) ? (string) @$cleaners_details['first_name'] : "",
                                            'last_name' => ($cleaners_details['last_name']) ? (string) @$cleaners_details['last_name'] : "",
                                            'phone_number' => ($cleaners_details['phone_number']) ? (string) @$cleaners_details['phone_number'] : "",
                                            'gender' => ($cleaners_details['gender']) ? (string) @$cleaners_details['gender'] : "",
                                            'image' => (!empty(@$cleaners_details['image'])) ? $this->get_userimage($detail_values['image']) : "",
                                            'is_favourite' => ($favourite) ? $favourite : "",
                                            'is_super_cleaner' => ($cleaner_status) ? $cleaner_status : "",
                                            'is_cleaner_busy' => ($cleaner_busy) ? (string) @$cleaner_busy : "",
                                            'ratings' => (string) $this->ratingFormat(@$cleaners_details->rating),
                                        ];
                                        $i++;
                                        $total_cleaner_found++;

                                    }

                                

                                    /* Break loop when the number of cleaners greater than 5 */

                                    if ($total_cleaner_found == 5) {
                                        break;
                                    }

                                }

                                Log::Info("addadvanceBookings OUPUT 2: " . json_encode($detail_values));
                            }

                        }

                    }

                }

                Log::Info("total_cleaner_found 3: " . $total_cleaner_found);
                Log::Info("addadvanceBookings 3 : " . json_encode($detail_values));

                
                if ($total_cleaner_found > 0) {
                    $this->success("Cleaners Found", $detail_values);
                } else {
                    $this->error("Currently you don't have any favorite cleaner. Press Send Request and we will assign you a cleaner.", ['err' => 7]);
                }

            }             
            else
            {
                if($check_user_exists['authenticate_status'] == 1)
                {
                    $this->error("Sorry, you can't make this booking. We are reviewing your account & will notify you soon! Thank you for your patience!", ['err' => 1]);
                }
                else
                {
                    $this->error("Please complete your account verification process under Account Verification section in the app.", ['err' => 1]);
                }
            }

        } else {
            $this->error("User not exists", []);
        }
    }
    
    /* Function to create booking with advanced bookings 
    * 2. Mass Blast to cleaners 
    * a. Send request to cleaners that has a clean that ends 1 hour before the requested
    * clean or starts one hour after the requested clean within the area, within a 5 km 
    * radius i. this should be a 1-minute interval
    * b. send request to cleaners that have a clean in that area within a 5km radius.
    * c. Send request to cleaners that have selected preferred work location in the area 
    * where the booking is located.
    * d. Send request to all cleaners in GTA    
    */
    public function createAdvancedBookings(Request $request)
    {
        $input = $request->all();

        $exclude_previous_cleaner = 0;
        $booking_id = 0;
        if(array_key_exists('booking_id',$input) && $input['booking_id']>0)
        {
            $booking_id = $input['booking_id'];            
            $where_array = ['id' => $booking_id];
            $get_booking_details = Bookings::where($where_array)->first(); 
            $exclude_previous_cleaner = $get_booking_details['service_provider_id'];
            
            Bookings::where(['id' => $booking_id])->update(['exclude_previous_cleaner'=>$exclude_previous_cleaner,'service_provider_id'=>'','cancelled_by'=>'','is_cancelled'=>0,'advance_fav_cleaner_notify'=>2]);
        }
        else
        {
            $this->validation(
                $request->all(),
                [
                    'booking_date' => 'required',
                    'space_id' => 'required',
                    'service_start_time' => 'required',
                    'service_end_time' => 'required',
                    'booking_hours' => 'required',
                    'booking_price' => 'required',
                    'booking_type' => 'required',
                    'booking_frequency' => 'required',
                    'booking_address' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'area_of_region' => 'required',
                ]
            );     
        }
       
        Log::Info("createAdvancedBookings PARAM:" . json_encode($input));
        
        $user_exists = Users::where('id', $this->userId)->first();
        if (!empty($user_exists)) {
            
            if ($user_exists['status']  == 1) 
            {

                $timezone = $user_exists['timezone'];

                if($booking_id == 0)
                {
                    $service_start_time_user = $input['service_start_time'];
                    $service_end_time_user = $input['service_end_time'];
                    $booking_date_user = $input['booking_date'];
                    
                    $service_start_time_utc = $this->userTimeToUTCTime($service_start_time_user, $timezone);
                    $service_end_time_utc = $this->userTimeToUTCTime($service_end_time_user, $timezone);
                    $booking_date_utc = date('Y-m-d', strtotime($service_start_time_utc));
                    
                    $this->checkValidBookingTime($service_start_time_user,$service_end_time_user);

                    /* check if booking exists in same time and date for a space */
                    $home_owner_booking_exists = $this->isHomeOwnerBookingExists($this->userId,$input['space_id'],$service_start_time_utc,$service_end_time_utc);
                    if($home_owner_booking_exists)
                    {
                        $this->error("You currently have an existing booking for this time. Please select another time to schedule.");
                    }
                }


                if (array_key_exists('service_provider_id',$input) && !empty($input['service_provider_id']))
                {

                    $exclude_pending_cleaner_sql = $this->get_pending_complete_cleaners_sql($input['service_provider_id']);
                    $exclude_pending_complete_cleaners = DB::select($exclude_pending_cleaner_sql);

                    $is_busy = $this->isCleanerBusy($input['service_provider_id'],$service_start_time_utc,$service_end_time_utc);
                    if($is_busy || !empty($exclude_pending_complete_cleaners))
                    {
                        $where = ['service_provider_id' => $input['service_provider_id'], 'user_id' => $this->userId, 'status' => 'favourite'];
                        $check_favourite = Favourites::where($where)->first();
                        if (!empty($check_favourite)) 
                        {
                            $body = "Sorry your favorite cleaner is not available at that time. Press Send Request and we will assign you a cleaner.";
                        }
                        else
                        {
                            $body = "Sorry the selected cleaner is not available at that time. Press Send Request and we will assign you a cleaner";                    
                        }
                    }                    

                    $instructions = (!empty($input['special_instructions'])) ? $input['special_instructions'] : "";
                    $services = (!empty($input['booking_services'])) ? $input['booking_services'] : "";                    
                
                    $job_id = $this->generateUniqueJobID();
                    $booking_array = ['user_id' => $this->userId,
                        'service_provider_id' => $input['service_provider_id'],
                        'space_id' => $input['space_id'],
                        'booking_services' => $services,
                        'booking_date' => $booking_date_utc,
                        'service_start_time' => $service_start_time_utc,
                        'service_end_time' => $service_end_time_utc,
                        'booking_hours' => $input['booking_hours'],
                        'booking_price' => $this->amountToFloat($input['booking_price']),
                        'booking_type' => $input['booking_type'],
                        'booking_frequency' => $input['booking_frequency'],
                        'booking_address' => $input['booking_address'],
                        'latitude' => $input['latitude'],
                        'longitude' => $input['longitude'],
                        'special_instructions' => $instructions,
                        'booking_status' => 0,
                        'is_cancelled' => 0,
                        'job_id' => $job_id,
                    ];

                    /* Save the booking row in bookings table */
                    $create_bookings = Bookings::create($booking_array);
                    $booking_id = $create_bookings['id'];
                 
                    
                    $time = date("H:i", strtotime($service_start_time_user));
                    $end_time = date("H:i", strtotime($service_end_time_utc));
                    $service_provider_details = Users::where('id', $input['service_provider_id'])->first();
                    $payload['title'] = 'New Booking';
                    $payload['body'] = 'You have new booking.';
                    $payload['value'] = $booking_id;
                    $payload['type'] = 'advanced';
                    $payload['user_type'] = 'cleaner';

                    /* Send Notification to the cleaner of advance booking */
                    $array = ['sender_id' => $this->userId, 'receiver_id' => $input['service_provider_id'], 'booking_id' => $create_bookings['id'], 'status' => '0'];
                    $notifications = Notifications::create($array);                        

                    if ($service_provider_details['push_notification'] == 1) {
                        $this->send_notification($service_provider_details['device_token'], $payload);                                                
                    }
                    if (!empty($booking_id)) {
                        $this->success("Your Cleaner has been notified of your request date. A confirmation will be sent to you shortly.", $booking_id);
                    } else {
                        $this->error("Bookings not Created");
                    }
                    
                } else 
                {

                    // MASS BLAST section => START
                    if($booking_id == 0)
                    {
                        $input['service_start_time_user'] = $service_start_time_user;
                        $input['service_end_time_user'] = $service_end_time_user;

                        $input['service_start_time'] = $service_start_time_utc;
                        $input['service_end_time'] = $service_end_time_utc;

                        $input['booking_date'] = $booking_date_utc;                                            
                        
                    }
                    $input['booking_id'] = $booking_id; // for advance fav cleanr idle/reject              
                    $input['exclude_previous_cleaner'] = $exclude_previous_cleaner; // for advance fav cleanr idle/reject dont send to this cleaner
                                        
                    $input['mass_blast_search'] = 'one_hour_before_after';

                    log::Info(" CREATE ADVANCE BOOKING PARAM ::: ".json_encode($input));
                    $notify_cleaner_response = $this->sendRequestToRecentClosedCleaners($input,$timezone,$this->add_advanced_booking_distance);
                    
                    if($notify_cleaner_response['is_notify'] == 0) // does not find any cleaner with in recent work area
                    {
                        $input['mass_blast_search'] = "current_clean_in_area";
                        $notify_cleaner_response = $this->getCleanersWithPreviousClean($input,$timezone,$this->add_advanced_booking_distance);                    
                    }
                    
                    if($notify_cleaner_response['is_notify'] == 0) // does not find any cleaner with in recent work area
                    {
                        $input['mass_blast_search'] = "preferred_work_area";
                        $notify_cleaner_response = $this->getCleanersWithinPreferredWorkArea($input,$timezone);                    
                    }

                    if($notify_cleaner_response['is_notify'] == 0) // does not find any cleaner with in recent work area
                    {
                        $input['mass_blast_search'] = "gta";
                        $notify_cleaner_response = $this->getCleanersGTA($input,$timezone);                    
                    }
                    // MASS BLAST section => END

                    if($notify_cleaner_response['booking_id'] > 0)
                    {
                        $this->success("Cleaners have been notified!\nWe are matching a cleaner with your request! We will notify you shortly!", $notify_cleaner_response['booking_id']);
                    }
                    else
                    {
                        $this->error("Sorry no available cleaners at this time. Please try again in some time", ['err' => 9]);
                    }
                   
                }
            } 
            else
            {
                if($user_exists['authenticate_status'] == 1)
                {
                    $this->error("Sorry, you can't make this booking. We are reviewing your account & will notify you soon! Thank you for your patience!", ['err' => 1]);
                }
                else
                {
                    $this->error("Please complete your account verification process under Account Verification section in the app.", ['err' => 1]);
                }
            }
        } else {
            $this->error("User does not exist", ['err' => 0]);
        }
    }

    /* Function to cancel bookings request */

    public function cancelBookingsRequest(Request $request)
    {

        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
            ]
        );

        /* Check user that cancel booking exist or not */

        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) 
        {
            $user_type = $check_user_exists['user_type'];

            /* Check the user cancel booking is of cleaner type */

            if ($user_type == 'cleaner') 
            {
                $array = ['id' => $input['booking_id'], 'service_provider_id' => $this->userId];
                $check_booking = Bookings::where($array)->first();

                /* Check the booking exists with that cleaner */

                if (!empty($check_booking)) 
                {
                    $check_booking_type = $check_booking['booking_type'];

                    /* Check booking type is of instant type */

                    if ($check_booking_type == 'instant') 
                    {
                        $get_current_time = strtotime(date("Y-m-d H:i:s"));
                        $booking_accept_time = strtotime($check_booking['accept_at']);
                        $diff = (abs($get_current_time - $booking_accept_time) / 60);
                        $round_values = round($diff, 0);

                        /* Check user cancel booking under 5 minutes */

                        if ($round_values <= 5) 
                        {
                            $homeowner_details = $check_booking['user_id'];
                            $user_details = Users::where('id', $homeowner_details)->first();
                            $user_device_token = $user_details['device_token'];                            
                            $cancelled_array = [
                                'is_cancelled' => 1,
                                'cancelled_by' => $this->userId,
                                'is_in_progress' => 0,
                                'is_on_route' => 0
                                
                            ];                            
                            /* Update booking in booking table when cleaner cancel booking under 5 minutes */

                            $update_value = Bookings::where(['id' => $input['booking_id'], 'service_provider_id' => $this->userId])->update($cancelled_array);
                            if ($update_value == 1) {

                                // this is for to delete all clear notification
                                $this->deleteNotification($input['booking_id']);

                                /* Send notification for cancellation of booking */
                                $payload['title'] = 'Booking Cancelled';
                                $payload['body'] = 'Your cleaner has canceled. Please create a new booking with new cleaner.';

                                $this->send_notification($user_device_token, $payload);
                            }
                        } 
                        else 
                        {
                            $this->error("Need Confirmation for ratings reductions");
                        }

                        /* Enter if booking is not instant */

                    } 
                    else 
                    {
                        $get_current_time = strtotime(date("Y-m-d H:i:s"));
                        $booking_accept_time = strtotime($check_booking['service_start_time']);
                        $diff = (abs($get_current_time - $booking_accept_time) / 60);
                        $round_values = round($diff, 0);

                        /* Check cleaner cancel bookings under 24 hours of service start time */

                        if ($round_values >= 1440) 
                        {
                            $check_booking = Bookings::where('id', $input['booking_id'])->first();
                            $where = ['service_provider_id' => $this->userId, 'user_id' => $check_booking['user_id'], 'status' => 'favourite'];

                            /* Check if the cleaner who cancel the booking if homeowner favourite or not */

                            $check_favourite = Favourites::where($where)->first();
                            if (!empty($check_favourite)) 
                            {
                                $update_array = [
                                    'is_cancelled' => 1,
                                    'cancelled_by' => $this->userId,
                                    'is_in_progress' => 0,
                                    'is_on_route' => 0
                                    
                                ];
                                // this is for to delete all clear notification
                                $this->deleteNotification($input['booking_id']);

                                $update_val = DB::table('bookings')->where('id', $input['booking_id'])->update($update_array);
                                $payload['title'] = 'Booking Cancelled';
                                $payload['body'] = 'Your cleaner has canceled. Please create a new booking with new cleaner.';
                                $device_token = $check_booking['device_token'];
                                if ($check_booking['push_notification'] == 1) 
                                {
                                    $this->send_cancel_notification($device_token, $payload);
                                }
                                $status = [
                                    'redirect_to_dashboard' => 1,
                                ];
                                $this->success("Redirect To Dashboard", $status);
                            } 
                            else 
                            {

                                $update_array = [
                                    'is_cancelled' => 1,
                                    'cancelled_by' => $this->userId,
                                    'is_in_progress' => 0,
                                    'is_on_route' => 0
                                    
                                ];
                                
                                // this is for to delete all clear notification
                                $this->deleteNotification($input['booking_id']);

                                $update_val = DB::table('bookings')->where('id', $input['booking_id'])->update($update_array);
                                $payload['title'] = 'Booking Cancelled';
                                $payload['body'] = 'Your cleaner has canceled. Please create a new booking with new cleaner.';
                                $device_token = $check_booking['device_token'];
                                if ($check_booking['push_notification'] == 1) {
                                    $this->send_cancel_notification($device_token, $payload);
                                }
                                $status = [
                                    'redirect_to_dashboard' => 1,
                                ];
                                $this->success("Redirect To Dashboard", $status);
                            }
                        } 
                        else 
                        {
                            $this->error("Reduct Cleaner Ratings by 0.2");
                        }
                    }
                }

                /* Check if the booking cancel the booking is a homeowner */
            } 
            else if ($user_type == 'homeOwner') 
            {
                $array = ['id' => $input['booking_id'], 'user_id' => $this->userId];
                $check_booking = Bookings::where($array)->first();
                if (!empty($check_booking)) 
                {
                    $check_booking_type = $check_booking['booking_type'];

                    /* Check if booking is of instant type */

                    if ($check_booking_type == 'instant') 
                    {
                        $date = date("Y-m-d H:i:s");
                        $get_current_time = strtotime(date("Y-m-d H:i:s"));
                        $booking_accept_time = strtotime($check_booking['accept_at']);
                        $diff = (abs($get_current_time - $booking_accept_time) / 60);
                        $round_values = round($diff, 0);

                        /* Check booking is cancel under 5 minutes */

                        if ($round_values <= 5) 
                        {
                            $cleaner_details = $check_booking['service_provider_id'];
                            $user_details = Users::where('id', $cleaner_details)->first();
                            $user_device_token = $user_details['device_token'];
                            $payload['title'] = 'Booking Cancelled';
                            $payload['body'] = 'Your homeowner has cancelled the booking';
                           
                            $cancelled_array = [
                                'is_cancelled' => 1,
                                'cancelled_by' => $this->userId,
                                'is_in_progress' => 0,
                                'is_on_route' => 0
                                
                            ];
                            // this is for to delete all clear notification
                            $this->deleteNotification($input['booking_id']);

                            /* Update booking status when cleaner cancel the booking */

                            $update_value = Bookings::where(['id' => $input['booking_id'], 'user_id' => $this->userId])->update($cancelled_array);
                            if ($user_details['push_notification'] == 1) 
                            {
                                $this->send_notification($user_device_token, $payload);
                            }
                            $this->success("Booking Cancelled Successfully", "");
                        } 
                        else 
                        {
                            $this->error("10% Penality for homeowner");
                        }
                    } 
                    else 
                    {
                        $get_current_time = strtotime(date("Y-m-d H:i:s"));
                        $booking_accept_time = strtotime($check_booking['service_start_time']);
                        $diff = (abs($get_current_time - $booking_accept_time) / 60);
                        $round_values = round($diff, 0);

                        /* Check booking is cancel under 24 hours hours */

                        if ($round_values >= 1440) {
                            
                            $cancelled_array = [
                                'is_cancelled' => 1,
                                'cancelled_by' => $this->userId,
                                'is_in_progress' => 0,
                                'is_on_route' => 0
                                
                            ];

                            /* Update cancel status in booking table */
                            $cleaner_details = $check_booking['service_provider_id'];
                            $user_details = Users::where('id', $cleaner_details)->first();
                            $user_device_token = $user_details['device_token'];
                            $update_value = Bookings::where(['id' => $input['booking_id'], 'user_id' => $this->userId])->update($cancelled_array);
                            $payload['title'] = 'Booking Cancelled';
                            $payload['body'] = 'Your homeowner has cancelled the booking';
                            
                            $cancelled_array = [
                                'is_cancelled' => 1,
                                'cancelled_by' => $this->userId,
                                'is_in_progress' => 0,
                                'is_on_route' => 0
                                
                            ];
                            // this is for to delete all clear notification
                            $this->deleteNotification($input['booking_id']);

                            if ($update_value == 1) {
                                $this->send_notification($user_device_token, $payload);
                            }
                            $this->success("Booking Cancelled", "");
                        } else {
                            $this->error("Reduct Penalty of user");
                        }
                    }
                } 
                else 
                {
                    $this->error("User Not Exists");
                }
            }
        }
    }

    /* Function to cancel instant booking request by cleaner */

    public function cancelInstantBookings(Request $request)
    {

        $input = $request->all();

        Log::Info("cancelInstantBookings INPUT" . json_encode($input));

        $this->validation(
            $request->all(),
            [
                'cancellation_request' => 'required',
                'booking_id' => 'required',
            ]
        );

        $service_provider_id = $this->userId;
        
        $booking_id = $input['booking_id'];
        if ($input['cancellation_request'] == 'yes') 
        {
            $where = ['id' => $input['booking_id'], 'service_provider_id' => $service_provider_id];
            $check_booking_exists = Bookings::where($where)->first();
            if (!empty($check_booking_exists)) 
            {

                $is_completed = (int)$check_booking_exists['is_completed'];
                $is_cancelled = (int)$check_booking_exists['is_cancelled'];
                if($is_completed == 0 && $is_cancelled == 0)
                {
                    $bookingedTime = strtotime($check_booking_exists['created_at']);
                    $currentDate = strtotime(date('Y-m-d H:i:s'));
                    $dateDiff =  $currentDate - $bookingedTime;
                    $cancellationHrs = $this->cancelled_hrs;

                    Log::Info("cancelInstantBookings COND" . $cancellationHrs . '==' . $dateDiff);
                    /* Update status of booking if user cancel the booking */
                    
                    if ($dateDiff > $cancellationHrs) 
                    {
                        /* If cleaner cancel the booking then his rating reduct by 0.2 points */
                        $this->error("WARNING, Are you sure you want to cancel this booking? There would be a decrease of 0.3 in your overall rating.", ['err' => 2]);
                    } 
                    else 
                    {
                        $home_owner_id = $check_booking_exists['user_id'];                    
                        $owner_details = Users::where('id', $home_owner_id)->first();
                       
                        $check_booking_exists['cancelled_by'] = 'cleaner';                
                        $check_booking_exists['homeowner_penalty_percent'] = 0;
                        $this->refundHomeOwnerForCancellation($home_owner_id,$check_booking_exists);
                        
                        // this is for to delete all clear notification
                        $this->deleteNotification($input['booking_id']);
                        
                        $payload['title'] = 'Booking Cancelled';
                        $payload['body'] = 'Your booking is cancelled. Please create a new booking with another cleaner';
                        
                        if ($owner_details['push_notification'] == 1) {
                            $notify_user = $this->send_cancel_notification($owner_details['device_token'], $payload);
                        }
                        $this->success("Booking cancelled successfully", ['success' => 3]);
                    }
                }
                else
                {
                    if($is_cancelled == 1)
                    {
                        $this->error("Booking already Cancelled", ['err' => 0]);
                    }
                    else
                    {
                        $this->error("Booking already completed", ['err' => 0]);
                    }
                }
                
            } 
            else 
            {
                $this->error("Booking does not exist.", ['err' => 1]);
            }
        } 
        else 
        {
            $this->error("Cancellation Process Discarded", ['err' => 0]);
        }
    }

    /* Function to cancel advanced request by cleaner */

    public function cancelAdvancedBookings(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'cancellation_request' => 'required',
                'booking_id' => 'required',
            ]
        );

        $booking_id = $input['booking_id'];
        $service_provider_id = $this->userId;
        if ($input['cancellation_request'] == 'yes') 
        {
            $where = ['id' => $booking_id, 'service_provider_id' => $service_provider_id ];
            $check_booking_exists = Bookings::where($where)->first();
            if (!empty($check_booking_exists)) 
            {

                $is_completed = (int)$check_booking_exists['is_completed'];
                $is_cancelled = (int)$check_booking_exists['is_cancelled'];
                if($is_completed == 0 && $is_cancelled == 0)
                {
                    $bookingStartTime = strtotime($check_booking_exists['service_start_time']);
                    $currentDate = strtotime(date('Y-m-d H:i:s'));
                    $dateDiff = $bookingStartTime - $currentDate;
                    $cancellationHrs = $this->cancelled_hrs_adv;

                    /* Update status of booking if user cancel the booking */                    
                    if ($dateDiff >= $cancellationHrs) 
                    {
                                            
                        $home_owner_id = $check_booking_exists['user_id'];                    
                        $owner_details = Users::where('id', $check_booking_exists['user_id'])->first();                    
                        $check_booking_exists['cancelled_by'] = 'cleaner';                
                        $check_booking_exists['homeowner_penalty_percent'] = 0;
                        $this->refundHomeOwnerForCancellation($home_owner_id,$check_booking_exists);
                        
                        // this is for to delete all clear notification
                        $this->deleteNotification($input['booking_id']);

                        $payload['title'] = 'Booking Cancelled';
                        $payload['body'] = 'Your booking is cancelled. Please create a new booking with another cleaner';
                        if ($owner_details['push_notification'] == 1) 
                        {
                            $notify_user = $this->send_cancel_notification($owner_details['device_token'], $payload);
                        }
                        $this->success("Booking cancelled successfully", ['success' => 3]);
                        
                    } 
                    else 
                    {
                        $this->error("WARNING, Are you sure you want to cancel this booking? There would be a decrease of 0.3 in your overall rating.", ['err' => 2]);
                    }
                    
                }
                else
                {
                    if($is_cancelled == 1)
                    {
                        $this->error("Booking already Cancelled", ['err' => 0]);
                    }
                    else
                    {
                        $this->error("Booking already completed", ['err' => 0]);
                    }
                }

                
            } 
            else 
            {
                $this->error("Booking does not exist.", ['err' => 1]);
            }
        } 
        else 
        {
            $this->error("Cancellation Process Discarded", ['err' => 0]);
        }
    }

    /* Function to cancel instant bookings from homeowner */

    public function cancelInstantBookingFromHomeowner(Request $request)
    {

        $this->validation(
            $request->all(),
            [
                'cancellation_request' => 'required',
                'booking_id' => 'required',
            ]
        );
        $input = $request->all();

        Log::Info("cancelInstantBookingFromHomeowner ::: " . json_encode($input));

        $userId = $this->userId;
        $booking_id = $input['booking_id'];
        
        if ($input['cancellation_request'] == 'yes') 
        {

            /* cut penality by 10% */

            $where = ['id' => $booking_id, 'user_id' => $userId];
            $check_booking_exists = Bookings::where($where)->first();
            if (!empty($check_booking_exists)) 
            {
                $is_completed = (int)$check_booking_exists['is_completed'];
                $is_cancelled = (int)$check_booking_exists['is_cancelled'];
                $service_provider_id = (int)$check_booking_exists['service_provider_id'];
                $booking_status = (int)$check_booking_exists['booking_status'];

                if($is_completed == 0 && $is_cancelled == 0)
                {
                    $bookingedTime = strtotime($check_booking_exists['created_at']);
                    $currentDate = strtotime(date('Y-m-d H:i:s'));
                    $dateDiff =  $currentDate - $bookingedTime;
                    $cancellationHrs = $this->cancelled_hrs;
                   
                    Log::Info("==================\n bookingStartTime : ".$check_booking_exists['service_start_time']);
                    Log::Info("==================\n NOW : ".date('Y-m-d H:i:s'));
                    
                    Log::Info("==================\ndateDiff : ".$dateDiff);
                    Log::Info("==================\ncancellationHrs : ".$cancellationHrs);
                   
                    $check_penalty = 1;
                    if(empty($service_provider_id) && $booking_status == 0)
                    {
                        $check_penalty = 0;

                    }
                    
                    if ($check_penalty == 1 && $dateDiff > $cancellationHrs) 
                    {                                                
                        $this->error("Are you sure you want to cancel this booking? There would be a 10% penalty.", ['err' => 2]);
                    } 
                    else 
                    {                        
                        $check_booking_exists['cancelled_by'] = 'homeOwner';                
                        $check_booking_exists['homeowner_penalty_percent'] = 0;
                        $this->refundHomeOwnerForCancellation($userId,$check_booking_exists);

                        // this is for to delete all clear notification
                        $this->deleteNotification($input['booking_id']);

                        if(!empty($check_booking_exists['service_provider_id']))
                        {
                            $get_cleaner_details = Users::where('id', $check_booking_exists['service_provider_id'])->first();
                            $payload['title'] = 'Booking Cancelled';
                            $payload['body'] = 'Your homeowner has cancelled the booking request.';
                            $device_token = $get_cleaner_details['device_token'];
                            if ($get_cleaner_details['push_notification'] == 1) 
                            {
                                $this->send_cancel_notification($device_token, $payload);
                            }
                        }
                        
                        $this->success("Booking cancelled Successfully", ['success' => 1]);
                    }
                }
                else
                {
                    if($is_cancelled == 1)
                    {
                        $this->error("Booking already Cancelled", ['err' => 0]);
                    }
                    else
                    {
                        $this->error("Booking already completed", ['err' => 0]);
                    }
                }
                
            } 
            else 
            {
                $this->error("Booking does not exist.", ['err' => 1]);
            }
        } 
        else 
        {
            $this->error("Cancellation Process Discarded", ['err' => 0]);
        }
    }

    /* Function to cancel advanced bookings from homeowner */

    public function cancelAdvancedBookingFromHomeowner(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
            ]
        );

        $userId = $this->userId;
        $booking_id = $input['booking_id'];

        
        /* cut penality by 10% */

        $where = ['id' => $booking_id, 'user_id' => $userId];
        $check_booking_exists = Bookings::where($where)->first();
        if (!empty($check_booking_exists)) 
        {

            $is_completed = (int)$check_booking_exists['is_completed'];
            $is_cancelled = (int)$check_booking_exists['is_cancelled'];
            $service_provider_id = (int)$check_booking_exists['service_provider_id'];
            $booking_status = (int)$check_booking_exists['booking_status'];
            if($is_completed == 0)
            {
                $bookingStartTime = strtotime($check_booking_exists['service_start_time']);
                $currentDate = strtotime(date('Y-m-d H:i:s'));
                
                $dateDiff =  $bookingStartTime - $currentDate;
                $cancellationHrs = $this->cancelled_hrs_adv;

            
                                    
                if ($check_booking_exists['advance_fav_cleaner_notify'] == 1) 
                {
                    $allow_cancel = 1;
                    $is_penalty = 0;
                }
                else
                {
                    if($is_cancelled == 1)
                    {
                        $allow_cancel = 0;
                        $is_penalty = 0;
                    }
                    else
                    {
                        $allow_cancel = 1;

                        if(empty($service_provider_id) && $booking_status == 0)
                        {
                            $is_penalty = 0;

                        }
                        else
                        {
                            if($dateDiff > $cancellationHrs)
                            {
                                $is_penalty = 0;
                            }
                            else
                            {
                                $is_penalty = 1;
                            }
                        }
                        
                    }
                }                
                
                if($allow_cancel == 1)
                {
                    if($is_penalty == 0)
                    {
                        
                        $check_booking_exists['cancelled_by'] = 'homeOwner';                
                        $check_booking_exists['homeowner_penalty_percent'] = 0;
                        $this->refundHomeOwnerForCancellation($userId,$check_booking_exists);

                        // this is for to delete all clear notification
                        $this->deleteNotification($input['booking_id']);

                        if(!empty($check_booking_exists['service_provider_id']) && $is_cancelled == 0)
                        {
                            $get_cleaner_details = Users::where('id', $check_booking_exists['service_provider_id'])->first();
                            $payload['title'] = 'Booking Cancelled';
                            $payload['body'] = 'Your homeowner has cancelled the booking request.';
                            $device_token = $get_cleaner_details['device_token'];
                            if ($get_cleaner_details['push_notification'] == 1) 
                            {
                                $this->send_cancel_notification($device_token, $payload);
                            }
                        }
                        
                        $this->success("Booking cancelled Successfully", ['success' => 1]);
                    }
                    else
                    {
                        $this->error("Are you sure you want to cancel this booking? There would be a 10% penalty.", ['err' => 2]);
                    }
                }
                else
                {
                    $this->error("Booking already Cancelled", ['err' => 0]);
                }
                
            }
            else
            {
                $this->error("Booking already completed", ['err' => 0]);
            }

            
        } 
        else 
        {
            $this->error("Booking does not exist.", ['err' => 1]);
        }
    
    }

    /* Function to get all extra services */

    public function getAllExtraServices(Request $request)
    {
        $input = $request->all();
        $this->validation($request->all(), [
            "mySpaceId" => "required",
        ]);

        $userDetails = Users::where('id', $this->userId)->first();

        Log::Info("getAllExtraServices mySpaceId=============".$request->input('mySpaceId'));

        $check_user_exists = Users::findOrFail($this->userId);

        if (!empty($check_user_exists)) {

            /* Get Basic Service Deatils from Basic Service Table */
            $check_space_exist = Myspace::where(['id' => $request->input('mySpaceId'), 'user_id' => $this->userId])->first();
            $total = 0;
            if ($check_space_exist) {
                $type = $check_space_exist['type'];
                if ('Condo' == $type) {
                    $bedrooms = ($check_space_exist['bedrooms']) ? $this->getBedroomsServiceHrs($check_space_exist['bedrooms']) : 0;
                    $bathrooms = ($check_space_exist['bathrooms']) ? $this->getBathroomsServiceHrs($check_space_exist['bathrooms']) : 0;
                    $dens = ($check_space_exist['dens']) ? $this->getDenServiceHrs($check_space_exist['dens']) : 0;
                    $total = $bedrooms + $bathrooms + $dens;
                } elseif ('House' == $type) {
                    $bedrooms = ($check_space_exist['bedrooms']) ? $this->getBedroomsServiceHrs($check_space_exist['bedrooms']) : 0;
                    $bathrooms = ($check_space_exist['bathrooms']) ? $this->getBathroomsServiceHrs($check_space_exist['bathrooms']) : 0;
                    $dens = ($check_space_exist['dens']) ? $this->getDenServiceHrs($check_space_exist['dens']) : 0;
                    $family_room = ($check_space_exist['family_room']) ? $this->getFamilyRoomServiceHrs($check_space_exist['family_room']) : 0;
                    $dining_room = ($check_space_exist['dining_room']) ? $this->getDiningRoomServiceHrs($check_space_exist['dining_room']) : 0;
                    $powder_room = ($check_space_exist['powder_room']) ? $this->getPowderRoomServiceHrs($check_space_exist['powder_room']) : 0;
                    $total = $bedrooms + $bathrooms + $dens + $family_room + $dining_room + $powder_room;
                } else {

                }
                $priceForAll = $this->hourly_rate * $total;
            } else {
                $this->error("Space id does not exist.");
            }
            $services = Basicservices::get()->first();
            $result = [];
            // Basic servicesarray after calculations
            $result[] = [
                'type' => $type,
                'time' => $total * 60,
                'price' => $priceForAll,
            ];

            /* Get Extra Service Details from Extra Service Table */

            $get_all_services = Extraservices::all();
            if (!empty($get_all_services)) {
                foreach ($get_all_services as $services) {
                    $check_time = $services['time'];
                    $values[] = [
                        'id' => $services['id'],
                        'name' => $services['name'],
                        'time' => $check_time,
                        'price' => $services['price'],
                        'selected_image' => $this->get_image_path($services['unselected_image'], 'extra_services'),
                        'unselected_image' => $this->get_image_path($services['image'], 'extra_services'),
                    ];
                }
            }
            $response['basic_services'] = $result;
            $response['extra_services'] = $values;
            $response['charges_percent'] = $this->skep_percent;

            //echo "<pre>";print_r($response);die;
            Log::Info("Extra chanrges : " . json_encode($response));

            $this->success("Services Found", $response);
        } else {
            $this->error("User not exists");
        }
    }

    /* Function to confirm Instant Bookings */

    public function confirmBooking(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
                'booking_type' => 'required',                
            ]
        );
        Log::Info("confirm Instant Booking: ",$input);

        $check_time_value = date("Y-m-d H:i:s");
        
        $time_for_wait = Waiting::where('waiting_for', 'time_between_notifications')->first();
        $time_wait = $time_for_wait['waiting_time'];
        
        $where = ['id' => $input['booking_id']];
        $check_booking_exists = Bookings::where($where)->first();
        if(empty($check_booking_exists))
        {
            $this->error("Sorry booking is no longer available");
        }
        else
        {
            if($check_booking_exists['is_cancelled'] == 1 || $check_booking_exists['is_orphan_booking'] == 1)
            {
                $this->error("Sorry booking is no longer available");
            }

        }
    
        $notification_details = Notifications::where(['receiver_id' => $this->userId,'booking_id'=>$input['booking_id']])->first();
        
        if(!empty($notification_details))
        {
            $created_at = $notification_details['created_at'];
            $check_time = date("Y-m-d H:i:s", strtotime('+' . $time_wait . ' seconds', strtotime($created_at)));

            Log::Info("confirmBooking : ".$check_time_value.'=='.$check_time.'==='.$time_wait);
            
            if ($check_time_value <= $check_time) {

                
                if (!empty($check_booking_exists)) {
    
                    if( (int) $check_booking_exists['service_provider_id'] > 0)
                    {
                        $this->error("Sorry this booking is no longer available");
                    }
                    else
                    {

                        $is_busy = $this->isCleanerBusy($this->userId,$check_booking_exists['service_start_time'],$check_booking_exists['service_end_time'],'instant');
                        if($is_busy)
                        {
                            $this->error("You already booked at this time");
                        }

                        // Checking customer id correspond to home owner
                        $stripeUserDetails = StripeUserDetails::where(['user_id' => $check_booking_exists['user_id']])->first();
                        if ($stripeUserDetails) {
                            $cusId = $stripeUserDetails->customer_id;
                        } else {
                            $this->error("Not found customer id");
                        }                        
    
                        $where = ['id' => $input['booking_id'], 'booking_type' => $input['booking_type']];
                        $update_val = ['service_provider_id' => $this->userId, 
                                        'booking_status' => 1, 
                                        'accept_at' => date("Y-m-d H:i:s"),
                                        'is_orphan_booking' =>0
                                    ];
                        $update = Bookings::where($where)->update($update_val);
                                    
                        $owner_details = Users::where('id', $check_booking_exists['user_id'])->first();
                                                                                                
                        $service_provider = Users::where('id', $this->userId)->first();
    
                        $cleaner_name = (!empty($service_provider['first_name'])) ? $service_provider['first_name'] : "User";
    
                        // this is for to delete all others clear notification (for mass blast )
                        $this->deleteNotification($input['booking_id'],$this->userId);
                        
                        $payload['title'] = "Booking Accepted";
                        $payload['body'] = $cleaner_name . ' accepted your cleaning request!';
                        $payload['type'] = 'instant accepted';
                        $payload['user_type'] = 'homeOwner';
    
                        /* Send Notification to homeowner that cleaner accept cleaning request */
                        if ($owner_details['push_notification'] == 1) {
                            $this->send_accept_notification($owner_details['device_token'], $payload);
                        }
    
                        if ($update == 1) {
                           
                            $payment_response = $this->processPayment($check_booking_exists,$cusId,$owner_details);
                            if($payment_response['is_error'] == 0)
                            {
                                $this->success("Instant Booking Accepted", ['charges' => $payment_response['charges']]);
                            }
                            else
                            {
                                $this->error($payment_response['err_msg']);
                            }
                            
                        } else {
                            $this->error("Booking Not Accepted");
                        }
                    }                
                } else {
                    $this->error("Booking not exists");
                }
            } else {
                $this->error("This Booking is expired");
            }

        }
        else
        {
            $this->error("Notification not exists");
        }        

    }

    /* 
        first it was getAdvancedBookingsValues change to getAllBookingRequests
        Function to Get all booking requests for 
        a particular type instant/advance bookings of a cleaner 
        for advance bookings that wait for acceptance within 12 hours 
        for instant bookings that wait for acceptance within 45 mins 
    */
    public function getAllBookingRequests(Request $request)
    {

        $input = $request->all();
        $this->validation(
            $request->all(),
            [
            //    'booking_type' => 'required',
            ]
        );
        Log::Info("getAllBookingRequests INPUT: " . json_encode($input));

        $time_for_wait = Waiting::where('waiting_for', 'time_between_notifications')->first();
        $time_wait = $time_for_wait['waiting_time'];
        
        $time_wait_advance = env("ADVANCE_ORPHAN_BOOKING_TIME");
        

        $sql = "SELECT notifications.receiver_id, notifications.sender_id, notifications.booking_id, 
                bookings.user_id,bookings.booking_status, bookings.is_cancelled, 
                bookings.created_at, bookings.space_id, bookings.booking_price, bookings.stripe_payout_fees, 
                bookings.booking_frequency, bookings.booking_services,bookings.booking_type,
                bookings.service_start_time, bookings.service_end_time, 
                bookings.booking_date, users.first_name, users.last_name, 
                users.profile_pic, users.selfie_image 
                FROM `notifications` LEFT JOIN `bookings` 
                ON (notifications.booking_id = bookings.id) LEFT JOIN `users` 
                ON (notifications.sender_id = users.id) WHERE 
                notifications.receiver_id = '".$this->userId."' AND 
                bookings.booking_status = '0' AND
                bookings.is_cancelled = 0  AND 
                bookings.is_orphan_booking = 0 
                ORDER BY bookings.booking_type DESC,notifications.created_at DESC
                ";

        Log::Info("\n------------\n getAllBookingRequests : ".$sql);
        $get_booking_details = DB::select( $sql);

        
        if (!empty($get_booking_details)) 
        {
            foreach ($get_booking_details as $booking_detail) 
            {                

                $created_date = $booking_detail->created_at;
                $current_date = date("Y-m-d H:i:s");
                if($booking_detail->booking_type == 'advanced')
                {
                    $end_date = date("Y-m-d H:i:s", strtotime('+'.$time_wait_advance.' minutes', strtotime($created_date)));
                }
                else
                {
                    $end_date = date("Y-m-d H:i:s", strtotime('+' . $time_wait . ' seconds', strtotime($created_date)));
                    
                }
                //Log::Info("\n-booking type end_date : current_date : ".$booking_detail->booking_type.'-'.$booking_detail->created_at.'=='.$end_date.'----'.$current_date);
                /* Check  booking acceptance time is greater than current date */
                if ($current_date <= $end_date) 
                {
                       
                    $user_selfie_image = $booking_detail->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                    
                    if($profile_pic == "")
                    {
                        if (!empty($booking_detail->profile_pic)) 
                        {
                            $pic_path = explode('/', $booking_detail->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) 
                            {
                                $profile_pic = $this->get_user_image_path($booking_detail->profile_pic, 'homeowner');
                            } 
                            else 
                            {
                                $profile_pic = $value->profile_pic;
                            }
                        } 
                        else 
                        {
                            $profile_pic = "";
                        }                        
                    } 
                    $check_user_exists = Users::where('id', $booking_detail->user_id)->first();
                    $timezone = $check_user_exists['timezone'];

                    $service_start_time_user = $this->utcTimeToUserTime($booking_detail->service_start_time, $timezone);
                    $service_end_time_user = $this->utcTimeToUserTime($booking_detail->service_end_time, $timezone);
                    $booking_date_user = date('Y-m-d', strtotime($service_start_time_user));

                    $myspace = Myspace::where('id', $booking_detail->space_id)->first();

                    $booking_price = $this->bookingPriceForCleaner($booking_detail->booking_price);
                    $response[] = [

                        'booking_id' => (int) $booking_detail->booking_id,
                        'booking_type' => $booking_detail->booking_type,
                        'first_name' => (string) @$booking_detail->first_name,
                        'last_name' => (string) @$booking_detail->last_name,
                        'booking_price' => (string) $booking_price,
                        'stripe_payout_fees' => (string) $this->stripe_fees,
                        'booking_frequency' => (string) @$booking_detail->booking_frequency,
                        'space_nickname' => (string) @$myspace['name'],
                        'booking_address' => (string) @$myspace['address'],
                        'latitude' => (string) @$myspace['latitude'],
                        'longitude' => (string) @$myspace['longitude'],
                        'booking_services' => (string) @$booking_detail->booking_services,
                        'booking_date' => (string) @date('Y-m-d', strtotime($booking_date_user)),
                        'booking_time' => (string) date("H:i:s", strtotime($service_start_time_user)),
                        'booking_end_time' => (string) date("H:i:s", strtotime($service_end_time_user)),
                        'profile_pic' => (string) @$profile_pic,
                    ];
                }
            }
            if (!empty($response)) 
            {

                $updateDetails = [
                   // 'status' => 1,
                    'notification_read' => 1,
                ];

                Notifications::where('receiver_id', $this->userId)->update($updateDetails);
                
                $data = ['bookings' => $response, 'service_fees' => $this->charge_deduct_cleaner_total_percent];
                Log::Info("getAllBookingRequests OUTPUT: " . json_encode($data));

                $this->success("Booking Found", $data);
            } 
            else 
            {
                $this->error("No Bookings Exists");
            }

        } 
        else 
        {
            $this->error("No Booking Exist");
        }
    }

    /* Confirm Advanced Booking by a cleaner */

    public function confirmAdvancedBooking(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
                'booking_type' => 'required',        
            ]
        );
        Log::Info("confirmAdvancedBooking " . json_encode($input));

        $time_wait_advance = env("ADVANCE_ORPHAN_BOOKING_TIME");
        
        $where = ['id' => $this->userId, 'account_blocked' => '0'];
        /* Check user exists or not temporarily blocked */
        $check_cleaner_exists = Users::where($where)->first();
        if (!empty($check_cleaner_exists)) {

            $where_array = ['id' => $input['booking_id'], 'booking_type' => 'advanced'];                
            $get_booking_details = Bookings::where($where_array)->first();

            
            if(empty($get_booking_details))
            {
                $this->error("Sorry booking is no longer available");
            }
            else
            {
                if($get_booking_details['booking_status'] == '1' || $get_booking_details['is_cancelled'] == 1 || $get_booking_details['is_orphan_booking'] == 1)
                {
                    $this->error("Sorry booking is no longer available");
                }

            }        

            $get_created_date = $get_booking_details['created_at'];
            $end_date = strtotime(date("Y-m-d H:i:s", strtotime('+'.$time_wait_advance.' minutes', strtotime($get_created_date))));
            $current_date = date("Y-m-d H:i:s");
            $current_time = strtotime($current_date);

            $service_start_time = strtotime($get_booking_details['service_start_time']);
            $time_diff = $service_start_time - $current_time;
            $time_diff_in_days = ceil($time_diff/86400);  //ceil because to round up the date 

            if ($end_date < $current_date) {  
                $this->error("Booking is Expired");
            }

            if ($input['booking_type'] == 'advanced') 
            {                
                                                      
                $is_busy = $this->isCleanerBusy($this->userId,$get_booking_details['service_start_time'],$get_booking_details['service_end_time'],$input['booking_id']);
                if($is_busy)
                {
                    $this->error("You already booked at this time");
                }
                $owner_details = Users::where('id', $get_booking_details['user_id'])->first();
                // Checking customer id correspond to home owner
                $stripeUserDetails = StripeUserDetails::where(['user_id' => $owner_details['id']])->first();
                if ($stripeUserDetails) {
                    $cusId = $stripeUserDetails->customer_id;
                } else {
                    $this->error("Not found customer id");
                }

                // this is for to delete all others clear notification (for mass blast )
                $this->deleteNotification($input['booking_id'],$this->userId);
                

                $cleaner_details = Users::where('id', $this->userId)->first();

                $cleaner_name = (!empty($cleaner_details['first_name'])) ? $cleaner_details['first_name'] : "User";

                $payload['title'] = 'Booking Accepted';
                $payload['body'] = $cleaner_name . ' accepted your cleaning request!';
                $payload['type'] = 'advanced accepted';
                $payload['user_type'] = 'homeOwner';

                /* Update the booking in database to confirm booking*/
                $update = ['booking_status' => '1', 
                            'service_provider_id' => $this->userId, 
                            'accept_at' => $current_date,
                            'is_orphan_booking' =>0
                        ];
                $update_val = Bookings::where(['id' => $input['booking_id'], 'booking_type' => 'advanced'])->update($update);

                /* Send Notification to homeowner that cleaner accept cleaning request */
                if ($owner_details['push_notification'] == 1) {
                    $this->send_accept_notification($owner_details['device_token'], $payload);
                }                                                    

                /** create stripe charge for advance booking **/

                if($time_diff_in_days <= env("STRIPE_PAYMENT_HOLD_DAYS"))
                {
                    
                    $payment_response = $this->processPayment($get_booking_details,$cusId,$owner_details);
                    if($payment_response['is_error'] == 0)
                    {
                        $this->success("Advance Booking Accepted", ['charges' => $payment_response['charges']]);
                    }
                    else
                    {
                        $this->error($payment_response['err_msg']);
                    }
                    
                }
                else{
                    $this->success("Advance Booking Accepted", ['charges' => []]);
                }                        

            
            
            } 
            else if ($input['booking_type'] == 'advanced_mass_blast') {
                                
                
                $is_busy = $this->isCleanerBusy($this->userId,$get_booking_details['service_start_time'],$get_booking_details['service_end_time']);
                if($is_busy)
                {
                    $this->error("You already booked at this time");
                }
                $owner_details = Users::where('id', $get_booking_details['user_id'])->first();
                // Checking customer id correspond to home owner
                $stripeUserDetails = StripeUserDetails::where(['user_id' => $owner_details['id']])->first();
                if ($stripeUserDetails) {
                    $cusId = $stripeUserDetails->customer_id;
                } else {
                    $this->error("Not found customer id");
                }

                $service_provider_details = Users::where('id', $this->userId)->first();
                $cleaner_name = (!empty($service_provider_details['name'])) ? $service_provider_details['name'] : "User";
                
                $payload['title'] = 'Booking Accepted';
                $payload['body'] = $cleaner_name . ' accepted your cleaning request!';
                $payload['type'] = 'advanced accepted';
                $payload['user_type'] = 'homeOwner';

                /* Update the booking in database to confirm booking*/
                $update = ['booking_status' => '1', 
                    'accept_at' => $current_date, 
                    'service_provider_id' => $this->userId,
                    'is_orphan_booking' =>0
                    ];
                $update_val = Bookings::where(['id' => $input['booking_id'], 'booking_type' => 'advanced'])->update($update);

                $this->deleteNotification($input['booking_id'],$this->userId);

                /* Send Notification to homeowner that cleaner accept cleaning request */
                if ($owner_details['push_notification'] == 1) {
                    $this->send_accept_notification($owner_details['device_token'], $payload);
                }    
                if ($update_val == 1) {

                    /** create stripe charge for advance booking mass_blast**/

                    if($time_diff_in_days <= env("STRIPE_PAYMENT_HOLD_DAYS"))
                    {
                        $payment_response = $this->processPayment($get_booking_details,$cusId,$owner_details);
                        if($payment_response['is_error'] == 0)
                        {
                            $this->success("Advance Booking Accepted", ['charges' => $payment_response['charges']]);
                        }
                        else
                        {
                            $this->error($payment_response['err_msg']);
                        }
                        
                    }
                    else
                    {
                        $this->success("Advance Booking Accepted", ['charges' => []]);
                    }
                    
                } else {
                    $this->error("Booking Not Confirmed");
                }                                                 
                
            
            }
        } else {
            $this->error("User not exists or temporarily blocked");
        }
    }

    /* 
    rejectAdvancedCleaning changed to rejectBookingRequest
    Function when cleaner reject the instant/advanced cleaning     
    */
    public function rejectBookingRequest(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
                'booking_type' => 'required',
            ]
        );

        Log::Info("=========== rejectBookingRequest input: ".json_encode($input));
        
        $where = ['id' => $this->userId, 'account_blocked' => '0'];

        /* Check user exists or not temporarily blocked */

        $check_cleaner_exists = Users::where($where)->first();
        if (!empty($check_cleaner_exists)) {
            $where_array = ['id' => $input['booking_id'], 'booking_status' => 0, 'booking_type' => $input['booking_type']];

            /* Check booking id exists with cleaner id */

            $get_booking_details = Bookings::where($where_array)->first();
            if (!empty($get_booking_details)) {
                
                $where = ['booking_id' => $input['booking_id'], 'receiver_id' => $this->userId];                    
                DB::table('notifications')->where($where)->delete();

                $home_owner_id = $get_booking_details['user_id'];                    
                $service_provider_id = $get_booking_details['service_provider_id'];    
                $booking_type = $get_booking_details['booking_type'];    

                $stripeData = [];
                if($booking_type == 'advanced')
                {
                    if($service_provider_id > 0)
                    {

                        $owner_details = Users::where('id', $home_owner_id)->first();

                        $service_start_time_user = $this->utcTimeToUserTime($get_booking_details['service_start_time'], $owner_details['timezone']);
                        

                        $cleaner_name = $check_cleaner_exists['first_name'].' '.$check_cleaner_exists['last_name'];

                        $stripeData = [
                            'cancelled_by' => 'cleaner',
                            'is_cancelled' => 1,                 
                            'is_in_progress' => 0,
                            'is_on_route' => 0,                     
                        ];                   
                        
                        $where = ['service_provider_id' => $service_provider_id, 'user_id' => $home_owner_id, 'status' => 'favourite'];
                        $check_favourite = Favourites::where($where)->first();
                        if (!empty($check_favourite)) 
                        {                            
                            $body = "Sorry your favorite cleaner ".$cleaner_name." is not available on ".date('Y-m-d',strtotime($service_start_time_user))." at ".date('h:i A',strtotime($service_start_time_user)).". Would you like us to provide you with another cleaner?";
                        }
                        else
                        {
                            $body = "Sorry the selected cleaner is not available at that time. Would you like us to provide you with another cleaner?";                    
                        }                        
                                                                                                
                        $advance_fav_cleaner_notify = 1;          
                        $payload = array();
                        $payload['title'] = 'Booking Not accepted';
                        $payload['body'] = $body;
                        $payload['type'] = 'advance_fav_cleaner_notify';
                        $payload['user_type'] = 'homeOwner';

                        $service_start_time_user = $this->utcTimeToUserTime($get_booking_details['service_start_time'], $owner_details['timezone']);
                        $service_end_time_user = $this->utcTimeToUserTime($get_booking_details['service_end_time'], $owner_details['timezone']);
                        
                        $notification_data= [];
                        $notification_data['type'] = 'advance_fav_cleaner_notify';
                        $notification_data['user_type'] = 'homeOwner';
                        $notification_data['booking_id'] = $input['booking_id'];
                        $payload['notification_data'] = $notification_data;

                        Log::Info("payload================".json_encode($owner_details));

                        $stripeData['advance_fav_cleaner_notify'] = $advance_fav_cleaner_notify;

                        $updateStripeData = Bookings::SaveChargesStripeData($stripeData, $input['booking_id']);   

                        if (!empty($owner_details['device_token']) && $owner_details['push_notification'] == 1) {
                            $notify_user = $this->send_cancel_notification($owner_details['device_token'], $payload);
                        }
                    }                    
    
                }
                
                // no need to send push in notification bell icon                    

                $this->success("Booking Rejected", "");
            
            } else {
                $this->error("Booking not exists");
            }
        } else {
            $this->error("User not exists or temporarily blocked");
        }
    }

    /* Function to check cleaner bookings in dashboard */
    public function getCleanersBookings(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_value' => 'required',
                //'timezone' => 'required',
                'page' => 'required',
            ]
        );

        Log::info('getCleanersBookings: ' . json_encode($input));
        /* Check user exists or temporarily blocked */
        $today = date("Y-m-d");
        $date = date("Y-m-d H:i:s");
        
        $response = $is_in_progress = $is_on_route = $pending_start = $pending_complete = $upcoming =array();
        $is_completed = $is_cancelled = array();
        $check_user_exists = Users::where(['id' => $this->userId, 'account_blocked' => 0])->first();

        if (!empty($check_user_exists)) 
        {
            $current_time = date("Y-m-d H:i:s");
            
            Log::info('user_type: ' . $check_user_exists['user_type']);
            if ($check_user_exists['user_type'] == 'cleaner') 
            {

                $limit = 10;
                if ($request->input('page') != 0) 
                {
                    $pageno = $request->input('page');
                } 
                else 
                {
                    $pageno = 1;
                }
                $start_from = ($pageno - 1) * $limit;
                /* When cleaner want to check its upcoming cleanings */
                Log::info('\n+++++++++++++++++++++\n++++++++++\nSTART_FROM===================: '.$start_from.'==='.$limit);                

                if ($input['booking_value'] == 'upcoming') 
                {
                    /* Check is filter value is empty or not */

                    if (!empty($input['filter_type'])) 
                    {

                        /* Check is filter type has today value */
                        
                        if ($input['filter_type'] == 'today') 
                        {
                            
                            $end_date = date("Y-m-d 23:59:59");

                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "' 
                                ) OR
                                service_end_time < '".$current_time."'
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "' 
                                ) OR
                                service_end_time < '".$current_time."'
                            )";
                                                        

                        } 
                        else if ($input['filter_type'] == 'week') 
                        {
                            
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('+7 days', (strtotime($date))));
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            (
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR 
                                service_end_time < '".$current_time."'
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;

                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                `is_in_progress` = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR 
                                service_end_time < '".$current_time."'
                            )";

                            /* Check is filter type has month value */

                        } 
                        else if ($input['filter_type'] == 'month') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('+1 month', (strtotime($date))));
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                ( 
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR
                                service_end_time < '".$current_time."'
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE `service_provider_id` = " . $this->userId . " AND `is_cancelled` = 0 AND `is_completed` = 0 AND `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR
                                service_end_time < '".$current_time."'
                            )";
                            
                        }
                        else if ($input['filter_type'] == 'all') 
                        {
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR                 
                                service_end_time < '".$current_time."'                                                                
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC 
                            LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE `service_provider_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                service_end_time < '".$current_time."'

                            )";
                        }
                        

                        /* When there is no filter */

                    } 
                    else 
                    {
                        $date = date("Y-m-d");
                        $end_date = date("Y-m-t", strtotime($date));
                        $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE `service_provider_id` = " . $this->userId . " AND 
                        `is_cancelled` = 0 AND 
                        `is_completed` = 0 AND 
                        `is_orphan_booking` = 0 AND 
                        ( 
                            is_in_progress = 1 OR 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                            ) OR 
                            service_end_time < '".$current_time."'                                                            
                        ) 
                        ORDER BY `service_start_time` ASC, is_in_progress DESC 
                        LIMIT " . $start_from . "," . $limit;
                        
                        $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image,users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE `service_provider_id` = " . $this->userId . " AND 
                        `is_cancelled` = 0 AND 
                        `is_completed` = 0 AND 
                        `is_orphan_booking` = 0 AND 
                        ( 
                            is_in_progress = 1 OR 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                            ) OR 
                            service_end_time < '".$current_time."'

                        )";
                    }

                    Log::info('UPCOMING: SQL: ' . $sql);
                    Log::info('UPCOMING SQL1: ' . $sql1);

                    $values = DB::select($sql);

                    $count_values = DB::select($sql1);
                    if (!empty($values)) 
                    {
                        foreach ($values as $value) 
                        {

                            /* Check user image has normal or social profile pic */

                            $user_selfie_image = $value->selfie_image;
                            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

                            
                            if($profile_pic == "")
                            {
                                if (!empty($value->profile_image)) 
                                {
                                    $pic_path = explode('/', $value->profile_image);
                                    $path_count = count($pic_path);
                                    if ($path_count == 1) 
                                    {
                                        $profile_pic = $this->get_user_image_path($value->profile_image, 'homeowners');
                                    } 
                                    else 
                                    {
                                        $profile_pic = $value->profile_image;
                                    }
                                } 
                                else 
                                {
                                    $profile_pic = "";
                                }
                            }

                            $space_values = Myspace::where('id', $value->space_id)->first();
                            $allStatus = $this->getBookingStatus($value);

                            $service_start_time_user = $this->utcTimeToUserTime($value->service_start_time, $check_user_exists['timezone']);
                            $service_end_time_user = $this->utcTimeToUserTime($value->service_end_time, $check_user_exists['timezone']);
                            $booking_date_user = date('Y-m-d', strtotime($service_start_time_user));
                            $booking_time = date("H:i:s", strtotime($service_start_time_user));

                            $booking_price = $this->bookingPriceForCleaner($value->booking_price);
                            $booking_data = [
                                'booking_id' => (int) $value->id,
                                'first_name' => (string) @$value->first_name,
                                'last_name' => (string) @$value->last_name,
                                'timezone' => (string) @$value->timezone,
                                'booking_price' => (string) $booking_price,
                                'stripe_payout_fees' => (string) $this->stripe_fees,
                                'booking_frequency' => (string) @$value->booking_frequency,
                                'booking_address' => (string) @$space_values['address'],
                                'space_nickname' => (string) @$space_values['name'],
                                'latitude' => (string) @$space_values['latitude'],
                                'longitude' => (string) @$space_values['longitude'],
                                'booking_services' => (string) @$value->booking_services,
                                'booking_start_time' => (string) $service_start_time_user,
                                'booking_end_time' => (string) $service_end_time_user,

                                'booking_date' => (string) @$booking_date_user,
                                'booking_time' => (string) $booking_time,
                                'profile_pic' => (string) @$profile_pic,
                                'is_cancelled' => (string) $value->is_cancelled,
                                'is_upcoming' => $allStatus['is_upcoming'],
                                'is_pending' => $allStatus['is_pending'],
                                'is_on_route' => $allStatus['is_on_route'],
                                'is_completed' => $allStatus['is_completed'],
                                'is_in_progress' => $allStatus['is_in_progress'],
                                'pending_start' => $allStatus['pending_start'],
                                'pending_complete' => $allStatus['pending_complete'],
                                'booking_status' => (string) @$value->booking_status,
                                'skep_percent' => $this->charge_deduct_cleaner_total_percent,
                            ];
                            if($allStatus['is_in_progress'] == 1)
                            {
                                array_push($is_in_progress,$booking_data);
                            }
                            else if($allStatus['is_on_route'] == 1)
                            {
                                array_push($is_on_route,$booking_data);
                            }
                            else if($allStatus['pending_start'] == 1)
                            {
                                array_push($pending_start,$booking_data);
                            }
                            else if($allStatus['pending_complete'] == 1)
                            {
                                array_push($pending_complete,$booking_data);
                            }
                            else 
                            {
                                array_push($upcoming,$booking_data);
                            }
                            
                            
                        }

                        if (!empty($count_values)) 
                        {
                            $count = count($count_values);
                        } 
                        else 
                        {
                            $count = 0;
                        }
                        
                        $response = array_merge($pending_start,$pending_complete,$is_in_progress,$is_on_route,$upcoming);
                        $result['count'] = $count;
                        $result['ratings'] = $this->ratingFormat($check_user_exists['rating']);
                        $result['service_fees'] = $this->charge_deduct_cleaner_total_percent;
                        if (!empty($response)) 
                        {
                            $result['response'] = $response;
                        } 
                        else 
                        {
                            $this->error("No Bookings Exists");
                        }
                        Log::info('RESPONSE  : ' . json_encode($result));
                        $this->success("Bookings Found Successfully", $result);
                    } 
                    else 
                    {
                        $this->error("No Bookings Exists");
                    }

                    /* Check cleaner past bookings */

                } 
                else if ($input['booking_value'] == 'history') 
                {
                    $date = date("Y-m-d H:i:s");

                    /* When cleaner want current day past bookings */

                    if (!empty($input['filter_type'])) 
                    {
                        if ($input['filter_type'] == 'today') 
                        {
                            $start_date = date("Y-m-d 00:00:00");
                            $end_date = date("Y-m-d 23:59:59");

                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "' 
                            ) AND 
                            (                                
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1                                
                                
                            ) AND 
                            `is_orphan_booking` = 0 
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today. "' 
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                
                            )  AND 
                            `is_orphan_booking` = 0 ";

                            /* When cleaner want last week past bookings */

                        } 
                        else if ($input['filter_type'] == 'week') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('-7 days', strtotime($date)));
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                
                            )  AND 
                            `is_orphan_booking` = 0  
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;

                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                            ) 
                            AND                              
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1 
                            ) AND 
                            `is_orphan_booking` = 0 ";

                            /* When cleaner want last month past bookings */

                        } 
                        else if ($input['filter_type'] == 'month') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('-1 month', (strtotime($date))));
                            
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id  WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "' 
                                
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1 
                                                              
                            ) AND 
                            `is_orphan_booking` = 0 
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;

                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id 
                            WHERE `service_provider_id` = " . $this->userId . " AND
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "' 
                                
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` =  1
                            ) AND 
                            `is_orphan_booking` = 0 
                            ";
                        }
                        else if ($input['filter_type'] == 'all')
                        {
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . "  AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1                               
                            )  
                            AND `is_orphan_booking` = 0  
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                            `service_provider_id` = " . $this->userId . " AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1                                 
                            ) 
                            AND `is_orphan_booking` = 0 ";
                        }

                        /* When there is no filter */

                    } 
                    else 
                    {
                        $date = date("Y-m-d");
                        $end_date = date("Y-m-d", strtotime('-1 month', (strtotime($date))));
                        $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                        `service_provider_id` = " . $this->userId . " AND 
                        (
                            DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                        ) AND 
                        (
                            `is_cancelled` = 1 OR 
                            `is_completed` = 1
                          
                        )  AND 
                        `is_orphan_booking` = 0 
                        ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;
                       
                        $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone FROM bookings LEFT JOIN `users` ON bookings.user_id = users.id WHERE 
                        `service_provider_id` = " . $this->userId . " AND 
                        (
                            DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                        ) AND 
                        (
                            `is_cancelled` = 1 OR 
                            `is_completed` = 1                             
                        ) AND 
                        `is_orphan_booking` = 0 ";
                    }

                    Log::info('HISTORY: getCleanersBookings SQL: ' . $sql);
                    Log::info('HISTORY: getCleanersBookings SQL1: ' . $sql1);
                    $values = DB::select($sql);
                    $count_values = DB::select($sql1);
                    if (!empty($values)) 
                    {
                        foreach ($values as $value) 
                        {
                            
                            $ratings = 0;
                            // booking rating
                            if($value->ratingGivenByHomeOwner == 1)
                            {
                                $get_ratings = Ratings::where('booking_id', $value->id)->first();
                            
                                if(!empty($get_ratings))
                                {                                
                                    $ratings = $get_ratings['ratings'];
                                }
                            }
                            

                            /* Check image is normal or social profile pic */
                            $user_selfie_image = $value->selfie_image;
                            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

                            if($profile_pic == "")
                            {
                                if (!empty($value->profile_image)) 
                                {
                                    $pic_path = explode('/', $value->profile_image);
                                    $path_count = count($pic_path);
                                    if ($path_count == 1) 
                                    {
                                        $profile_pic = $this->get_user_image_path($value->profile_image, 'homeowners');
                                    } 
                                    else 
                                    {
                                        $profile_pic = $value->profile_image;
                                    }
                                } 
                                else 
                                {
                                    $profile_pic = "";
                                }
                                
                            }

                            $allStatus = $this->getBookingStatus($value);
                            $space_values = Myspace::where('id', $value->space_id)->first();

                            $service_start_time_user = $this->utcTimeToUserTime($value->service_start_time, $check_user_exists['timezone']);
                            $service_end_time_user = $this->utcTimeToUserTime($value->service_end_time, $check_user_exists['timezone']);
                            $booking_date_user = date('Y-m-d', strtotime($service_start_time_user));
                            $booking_time = date("H:i:s", strtotime($service_start_time_user));

                            $stripe_amount_refunded = $value->stripe_refund_amount;
                            $referral_balance_refunded = $value->balance_refund;
                            $total_refund_amount = $this->amountToFloat($stripe_amount_refunded + $referral_balance_refunded);            

                            $booking_price = $this->bookingPriceForCleaner($value->booking_price);

                            $response[] = [
                                'booking_id' => (int) $value->id,
                                'first_name' => (string) @$value->first_name,
                                'last_name' => (string) @$value->last_name,
                                'timezone' => (string) @$value->timezone,
                                'booking_price' => (string) $booking_price,
                                'stripe_payout_fees' => (string) $this->stripe_fees,
                                'refunded_amount' => (string) $total_refund_amount,
                                'stripe_amount_refunded' => (string) $stripe_amount_refunded,
                                'referral_balance_refunded' => (string) $referral_balance_refunded,
                                'booking_frequency' => (string) @$value->booking_frequency,
                                'booking_address' => (string) @$space_values['address'],
                                'latitude' => (string) @$space_values['latitude'],
                                'longitude' => (string) @$space_values['longitude'],
                                'space_nickname' => (string) @$space_values['name'],
                                'booking_services' => (string) @$value->booking_services,
                                'booking_start_time' => (string) $service_start_time_user,
                                'booking_end_time' => (string) $service_end_time_user,
                                'booking_date' => (string) $booking_date_user,
                                'booking_time' => (string) $booking_time,
                                'profile_pic' => (string) @$profile_pic,
                                'is_cancelled' => (string) $value->is_cancelled,
                                'is_upcoming' => $allStatus['is_upcoming'],
                                'is_pending' => $allStatus['is_pending'],
                                'is_on_route' => $allStatus['is_on_route'],
                                'is_completed' => $allStatus['is_completed'],
                                'is_in_progress' => $allStatus['is_in_progress'],
                                'pending_start' => $allStatus['pending_start'],
                                'pending_complete' => $allStatus['pending_complete'],
                                'booking_status' => (string) @$value->booking_status,
                                'skep_percent' => $this->charge_deduct_cleaner_total_percent,
                                'ratings' => $this->ratingFormat(@$ratings)
                                
                            ];
                        }

                        if (!empty($count_values)) 
                        {
                            $count = count($count_values);
                        } 
                        else 
                        {
                            $count = 0;
                        }
                        $result['count'] = $count;
                        $result['ratings'] = $this->ratingFormat($check_user_exists['rating']);
                        $result['service_fees'] = $this->charge_deduct_cleaner_total_percent;
                        if (!empty($response)) 
                        {
                            $result['response'] = $response;
                        } 
                        else 
                        {
                            $this->error("No Bookings Exists");
                        }
                        $this->success("Bookings Found Successfully", $result);
                    } 
                    else 
                    {
                        $this->error("No Bookings Exists");
                    }
                }

                /* Check user type is a homeowner */

            } 
            else if ($check_user_exists['user_type'] == 'homeOwner') 
            {

                $limit = 10;
                if ($request->input('page') != 0) 
                {
                    $pageno = $request->input('page');
                } 
                else 
                {
                    $pageno = 1;
                }
                $start_from = ($pageno - 1) * $limit;

                /* When cleaner want to check its upcoming cleanings */

                if ($input['booking_value'] == 'upcoming') 
                {

                    $date = date("Y-m-d H:i:s");

                    /* When cleaner want current day bookings */

                    if (!empty($input['filter_type'])) 
                    {
                        if ($input['filter_type'] == 'today') 
                        {
                            $end_date = date("Y-m-d 23:59:59");
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0  AND 
                            `is_orphan_booking` = 0 AND 
                            (
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "'  
                                ) OR 
                                service_end_time < '".$current_time."'
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0  AND 
                            `is_orphan_booking` = 0 AND 
                            (
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "' 
                                ) OR
                                service_end_time < '".$current_time."'

                            )";

                            /* When cleaner want current week bookings */

                        } 
                        else if ($input['filter_type'] == 'week') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('+7 days', (strtotime($date))));
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0  AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR
                                service_end_time < '".$current_time."'

                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;

                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0  AND 
                            `is_orphan_booking` = 0 AND 
                            `is_cancelled` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR 
                                service_end_time < '".$current_time."'
                            )";

                            /* When cleaner want current month bookings */

                        } 
                        else if ($input['filter_type'] == 'month') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('+1 month', (strtotime($date))));
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` 
                            ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR
                                service_end_time < '".$current_time."'
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0  AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                (
                                    DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                                ) OR
                                service_end_time < '".$current_time."'
                            )";
                        }
                        else if ($input['filter_type'] == 'all') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('+1 month', (strtotime($date))));
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` 
                            ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0 AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR                                 
                                service_end_time < '".$current_time."'
                            ) 
                            ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            `is_cancelled` = 0 AND 
                            `is_completed` = 0  AND 
                            `is_orphan_booking` = 0 AND 
                            ( 
                                is_in_progress = 1 OR 
                                service_end_time < '".$current_time."'
                            )";
                        }

                        /* When there is no filter */

                    } 
                    else 
                    {
                        $date = date("Y-m-d");
                        $end_date = date("Y-m-t", strtotime($date));
                        $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                        users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                        `user_id` = " . $this->userId . " AND 
                        `is_cancelled` = 0 AND 
                        `is_completed` = 0  AND 
                        `is_orphan_booking` = 0 AND 
                        ( 
                            is_in_progress = 1 OR 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                            ) OR
                            service_end_time < '".$current_time."'
                        ) 
                        ORDER BY `service_start_time` ASC, is_in_progress DESC LIMIT " . $start_from . "," . $limit;

                        $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                        `user_id` = " . $this->userId . " AND 
                        `is_cancelled` = 0  AND 
                        `is_orphan_booking` = 0 AND 
                        `is_completed` = 0 AND 
                        ( 
                            is_in_progress = 1 OR 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $date . "' AND '" . $end_date . "'
                            ) OR
                            service_end_time < '".$current_time."'
                        )";
                    }

                    Log::info('HOMEOWNER UPCOMING SQL: ' . $sql);
                    Log::info('HOMEOWNER UPCOMING SQL1: ' . $sql1);

                    $values = DB::select($sql);
                    $count_values = DB::select($sql1);

                    if (!empty($values)) 
                    {
                        foreach ($values as $value) 
                        {

                            $get_ratings = Ratings::where('ratings_for', $value->service_provider_id)->orderBy('created_at', 'DESC')->first();
                            if (!empty($check_user_exists->is_super_cleaner)) 
                            {
                                if ($check_user_exists->is_super_cleaner == '1') 
                                {
                                    $is_super_cleaner = '1';
                                } 
                                else 
                                {
                                    $is_super_cleaner = '0';
                                }
                            } 
                            else 
                            {
                                $is_super_cleaner = '0';
                            }

                            
                            /* Check image is normal or social profile pic */
                            $user_selfie_image = $value->selfie_image;
                            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                            
                            if($profile_pic == "")
                            {
                                if (!empty($value->profile_image)) 
                                {
                                    $pic_path = explode('/', $value->profile_image);
                                    $path_count = count($pic_path);
                                    if ($path_count == 1) 
                                    {
                                        $profile_pic = $this->get_user_image_path($value->profile_image, 'cleaners');
                                    } 
                                    else 
                                    {
                                        $profile_pic = $value->profile_image;
                                    }
                                } 
                                else 
                                {
                                    $profile_pic = "";
                                }                                
                            }

                            $space_values = Myspace::where('id', $value->space_id)->first();
                            $allStatus = $this->getBookingStatus($value);

                            $service_start_time_user = $this->utcTimeToUserTime($value->service_start_time, $check_user_exists['timezone']);
                            $service_end_time_user = $this->utcTimeToUserTime($value->service_end_time, $check_user_exists['timezone']);
                            $booking_date_user = date('Y-m-d', strtotime($service_start_time_user));
                            $booking_time = date("H:i:s", strtotime($service_start_time_user));

                            $booking_data = [
                                'booking_id' => (int) $value->id,
                                'first_name' => (string) @$value->first_name,
                                'last_name' => (string) @$value->last_name,
                                'timezone' => (string) @$value->timezone,
                                'booking_price' => (string) $this->amountToFloat( @$value->booking_price),
                                'booking_frequency' => (string) @$value->booking_frequency,
                                'booking_address' => (string) @$space_values['address'],
                                'space_nickname' => (string) @$space_values['name'],
                                'latitude' => (string) @$space_values['latitude'],
                                'longitude' => (string) @$space_values['longitude'],
                                'booking_services' => (string) @$value->booking_services,
                                'booking_date' => (string) $booking_date_user,
                                'booking_time' => (string) $booking_time,
                                'booking_start_time' => (string) $service_start_time_user,
                                'booking_end_time' => (string) $service_end_time_user,
                                'profile_pic' => (string) @$profile_pic,
                                'ratings' => (string) $this->ratingFormat(@$value->rating),
                                'is_super_cleaner' => (string) @$is_super_cleaner,
                                'is_upcoming' => $allStatus['is_upcoming'],
                                'is_pending' => $allStatus['is_pending'],
                                'is_cancelled' => (string) $value->is_cancelled,
                                'is_on_route' => $allStatus['is_on_route'],
                                'is_completed' => $allStatus['is_completed'],
                                'is_in_progress' => $allStatus['is_in_progress'],
                                'pending_start' => $allStatus['pending_start'],
                                'pending_complete' => $allStatus['pending_complete'],
                                'booking_status' => (string) @$value->booking_status,
                                'service_fees' => $this->skep_percent,
                            ];

                            if($allStatus['is_in_progress'] == 1)
                            {
                                array_push($is_in_progress,$booking_data);
                            }
                            else if($allStatus['is_on_route'] == 1)
                            {
                                array_push($is_on_route,$booking_data);
                            }
                            else if($allStatus['pending_start'] == 1)
                            {
                                array_push($pending_start,$booking_data);
                            }
                            else if($allStatus['pending_complete'] == 1)
                            {
                                array_push($pending_complete,$booking_data);
                            }
                            else 
                            {
                                array_push($upcoming,$booking_data);
                            }
                        }

                        $result = [];
                        if (!empty($count_values)) 
                        {
                            $count = count($count_values);
                        } 
                        else 
                        {
                            $count = 0;
                        }

                        $response = array_merge($pending_start,$pending_complete,$is_in_progress,$is_on_route,$upcoming);
                        $result['count'] = $count;
                        $result['service_fees'] = $this->skep_percent;
                        if (!empty($response)) 
                        {
                            $result['response'] = $response;
                        } 
                        else 
                        {
                            $this->error("No Bookings Exists");
                        }
                        $this->success("Bookings Found Successfully", $result);
                    } 
                    else 
                    {
                        $this->error("No Bookings Exists");
                    }

                    /* When cleaner check past bookings */

                } 
                else if ($input['booking_value'] == 'history') 
                {

                    $date = $current_time = date("Y-m-d H:i:s");

                    /* When cleaner want current day past bookings */

                    if (!empty($input['filter_type'])) 
                    { 
                        if ($input['filter_type'] == 'today') 
                        {

                            $start_date = date("Y-m-d 00:00:00");
                            $end_date = date("Y-m-d 23:59:59");

                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "' 
                            ) AND 
                            (                            
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                                                
                            ) AND 
                            `is_orphan_booking` = 0 
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;

                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') =  '" . $today . "' 
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                
                                
                            ) AND 
                            `is_orphan_booking` = 0 ";

                            /* When cleaner want last week past bookings */

                        } 
                        else if ($input['filter_type'] == 'week') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('-7 days', strtotime($date)));
                            
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1                                                               
                            )  AND 
                            `is_orphan_booking` = 0 
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                
                                
                            ) AND 
                            `is_orphan_booking` = 0 ";

                            /* When cleaner want last month past bookings */

                        } 
                        else if ($input['filter_type'] == 'month') 
                        {
                            $date = date("Y-m-d");
                            $end_date = date("Y-m-d", strtotime('-1 month', (strtotime($date))));
                            
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id 
                            WHERE `user_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                
                                
                            ) AND                              
                            `is_orphan_booking` = 0 
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC, is_completed DESC LIMIT " . $start_from . "," . $limit;

                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id 
                            WHERE `user_id` = " . $this->userId . " AND 
                            (
                                DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                            ) AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                                
                                
                            ) AND 
                            `is_orphan_booking` = 0 
                            ";

                        }
                        else if ($input['filter_type'] == 'all') 
                        {
                            $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                            `user_id` = " . $this->userId . " AND 
                            (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1
                               
                                
                            ) AND 
                            `is_orphan_booking` = 0 
                            ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;
                            
                            $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, 
                            users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE
                             `user_id` = " . $this->userId . " AND 
                             (
                                `is_cancelled` = 1 OR 
                                `is_completed` = 1                                                               
                             ) AND 
                             `is_orphan_booking` = 0 ";

                            /* When cleaner want last month past bookings */

                        }

                        /* When there is no filter */

                    } 
                    else 
                    {
                        $date = date("Y-m-d");
                        $end_date = date("Y-m-d", strtotime('-1 month', (strtotime($date))));
                        $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                        `user_id` = " . $this->userId . " AND 
                        ( 
                            DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                        ) AND 
                        (
                            `is_cancelled` = 1 OR 
                            `is_completed` = 1
                        )  AND 
                        `is_orphan_booking` = 0 
                        ORDER BY DATE_FORMAT(`service_start_time`,'%Y-%m-%d') DESC,is_completed DESC LIMIT " . $start_from . "," . $limit;
                        
                        $sql1 = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, users.profile_pic as profile_image, users.selfie_image as selfie_image, users.timezone as timezone, users.rating as rating FROM bookings LEFT JOIN `users` ON bookings.service_provider_id = users.id WHERE 
                        `user_id` = " . $this->userId . " AND 
                        (
                            DATE_FORMAT(`service_start_time`,'%Y-%m-%d') BETWEEN  '" . $end_date . "' AND '" . $date . "'
                        ) AND 
                        (
                            `is_cancelled` = 1 OR 
                            `is_completed` = 1                                                       
                        )  AND 
                        `is_orphan_booking` = 0 ";
                    }

                    Log::info('HOMEOWNER HISTORY SQL: ' . $sql);
                    Log::info('HOMEOWNER HISTORY SQL1: ' . $sql1);

                    $values = DB::select($sql);
                    $count_values = DB::select($sql1);

                    if (!empty($values)) 
                    {
                        foreach ($values as $value) 
                        {
                            // cleaner rating
                            //$ratings = $this->ratingFormat(@$value->rating);

                            $ratings = 0;
                            // booking rating
                            if($value->ratingGivenByHomeOwner == 1)
                            {
                                $get_ratings = Ratings::where('booking_id', $value->id)->first();                            
                                if(!empty($get_ratings))
                                {                                
                                    $ratings = $get_ratings['ratings'];
                                }
                            }
                            

                            if (!empty($check_user_exists->is_super_cleaner)) 
                            {
                                if ($check_user_exists->is_super_cleaner == '1') 
                                {
                                    $is_super_cleaner = '1';
                                } 
                                else 
                                {
                                    $is_super_cleaner = '0';
                                }
                            } 
                            else 
                            {
                                $is_super_cleaner = '0';
                            }

                            /* Check image is normal or social profile pic */
                            $user_selfie_image = $value->selfie_image;
                            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';                            

                            if($profile_pic == "")
                            {
                                if (!empty($value->profile_image)) 
                                {
                                    $pic_path = explode('/', $value->profile_image);
                                    $path_count = count($pic_path);
                                    if ($path_count == 1) 
                                    {
                                        $profile_pic = $this->get_user_image_path($value->profile_image, 'homeowners');
                                    } 
                                    else 
                                    {
                                        $profile_pic = $value->profile_image;
                                    }
                                } 
                                else 
                                {
                                    $profile_pic = "";
                                }    
                            }
                            

                            $allStatus = $this->getBookingStatus($value);
                            $space_values = Myspace::where('id', $value->space_id)->first();

                            $service_start_time_user = $this->utcTimeToUserTime($value->service_start_time, $check_user_exists['timezone']);
                            $service_end_time_user = $this->utcTimeToUserTime($value->service_end_time, $check_user_exists['timezone']);
                            $booking_date_user = date('Y-m-d', strtotime($service_start_time_user));
                            $booking_time = date("H:i:s", strtotime($service_start_time_user));

                            $stripe_amount_refunded = $value->stripe_refund_amount;
                            $referral_balance_refunded = $value->balance_refund;
                            $total_refund_amount = $this->amountToFloat($stripe_amount_refunded + $referral_balance_refunded);            
                            
                            $response[] = [
                                'booking_id' => (int) $value->id,
                                'first_name' => (string) @$value->first_name,
                                'last_name' => (string) @$value->last_name,
                                'timezone' => (string) @$value->timezone,
                                'booking_price' => (string) $this->amountToFloat( @$value->booking_price),
                                'refunded_amount' => (string) $total_refund_amount,
                                'stripe_amount_refunded' => (string) $stripe_amount_refunded,
                                'referral_balance_refunded' => (string) $referral_balance_refunded,
                                'booking_frequency' => (string) @$value->booking_frequency,
                                'booking_address' => (string) @$space_values['address'],
                                'latitude' => (string) @$space_values['latitude'],
                                'longitude' => (string) @$space_values['longitude'],
                                'space_nickname' => (string) @$space_values['name'],
                                'booking_services' => (string) @$value->booking_services,
                                'booking_start_time' => (string) $service_start_time_user,
                                'booking_end_time' => (string) $service_end_time_user,
                                'booking_date' => (string) $booking_date_user,
                                'booking_time' => (string) $booking_time,
                                'profile_pic' => (string) @$profile_pic,
                                'ratings' => (string) $this->ratingFormat(@$ratings),
                                'is_super_cleaner' => (string) @$is_super_cleaner,
                                'is_upcoming' => $allStatus['is_upcoming'],
                                'is_pending' => $allStatus['is_pending'],
                                'is_cancelled' => (string) $value->is_cancelled,
                                'is_on_route' => $allStatus['is_on_route'],
                                'is_completed' => $allStatus['is_completed'],
                                'is_in_progress' => $allStatus['is_in_progress'],
                                'pending_start' => $allStatus['pending_start'],
                                'pending_complete' => $allStatus['pending_complete'],
                                'booking_status' => (string) @$value->booking_status,
                                'skep_percent' => $this->skep_percent,
                                'service_fees' => $this->skep_percent,
                            ];
                        }
                        if (!empty($count_values)) 
                        {
                            $count = count($count_values);
                        } 
                        else 
                        {
                            $count = 0;
                        }
                        $result['service_fees'] = $this->skep_percent;
                        $result['count'] = $count;
                        if (!empty($response)) 
                        {
                            $result['response'] = $response;
                        } 
                        else 
                        {
                            $this->error("No Bookings Exists");
                        }
                        $this->success("Bookings Found Successfully", $result);
                    } 
                    else 
                    {
                        $this->error("No Bookings Exists");
                    }
                }
            }

        } 
        else 
        {
            $this->error("User not exists or temporarily blocked");
        }
    }

    /* Function for dashboard details */

    public function getDashboardDetails(Request $request)
    {

        $input = $request->all();
        Log::info('getDashboardDetails PARAM: ' . json_encode($input));        

        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) 
        {
            
            $user_type = $check_user_exists['user_type'];
            $timezone = $check_user_exists['timezone'];

            $now = date("Y-m-d H:i:s");
            $now_user = date("Y-m-d H:i:s",strtotime($this->utcTimeToUserTime($now, $timezone)));
            $today_start_time_user = date("Y-m-d 00:00:00",strtotime($now_user));
            $today_end_time_user = date("Y-m-d 23:59:59",strtotime($now_user));

            $today_start_time_utc = $this->userTimeToUTCTime($today_start_time_user, $timezone);
            $today_end_time_utc = $this->userTimeToUTCTime($today_end_time_user, $timezone);

            /* Check if user is of cleaner type */
            /* Check if user has image of normal type or any social profile image */
            
            $selfie_image = $profile_image = (!empty($check_user_exists['selfie_image']))?$this->get_authenticate_certificate($check_user_exists['selfie_image'],'selfie_verification'):'';

            
            if($profile_image == "")
            {
                if (!empty($check_user_exists['profile_pic'])) 
                {
                    $pic_path = explode('/', $check_user_exists['profile_pic']);
                    $path_count = count($pic_path);
                    if ($path_count == 1) 
                    {
                        $profile_image = $this->get_user_image_path($check_user_exists['profile_pic'], 'homeowners');
                    } 
                    else 
                    {
                        $profile_image = $check_user_exists['profile_pic'];
                    }
                } 
                else 
                {
                    $profile_image = "";
                }
    
            }

            $is_spice_exists = Myspace::where('user_id', $this->userId)->count();
            if ($is_spice_exists) 
            {
                $space_status  = true;
            }
            else
            {
                $space_status  = false;
            }
            
            if ($user_type == 'cleaner') 
            {
                
                /* Check if the user is of cleaner type and user is super cleaner or not */

                if (!empty($check_user_exists['is_super_cleaner'])) 
                {
                    if ($check_user_exists['is_super_cleaner'] == '1') 
                    {
                        $is_super_cleaner = '1';
                    } 
                    else 
                    {
                        $is_super_cleaner = '0';
                    }
                } 
                else 
                {
                    $is_super_cleaner = '0';
                }                
                

                /* Ratings part: */
                $ratingDetails = [];
                
                $ratingDetails['is_last_service_rated'] = 1;
                $checkBookingRating = Bookings::where(['service_provider_id' => $this->userId, 'ratingGivenByCleaner' => 0, 'is_completed' => 1, 'is_cancelled' => 0,'is_orphan_booking'=>0])->first();
                if ($checkBookingRating) 
                {
                    $ratingDetails['booking_id'] = $checkBookingRating['id'];
                    $ratingDetails['is_last_service_rated'] = 0;
                    
                    $rating_homeowner_detail = Users::where(['id' => $checkBookingRating['user_id']])->first();

                    $ratingDetails['booking_type'] = ($rating_homeowner_detail['booking_type'] == 'advanced')?'Scheduled':'Instant';
                    $ratingDetails['user_id'] = $rating_homeowner_detail['id'];
                    $ratingDetails['user_selfie'] = (!empty($rating_homeowner_detail['selfie_image']))?$this->get_authenticate_certificate($rating_homeowner_detail['selfie_image'],'selfie_verification'):'';
                    $ratingDetails['user_first_name'] = (!empty($rating_homeowner_detail['first_name']))?$rating_homeowner_detail['first_name']:'';
                    $ratingDetails['user_last_name'] = (!empty($rating_homeowner_detail['last_name']))?$rating_homeowner_detail['last_name']:'';

                }
                
                $current_service_sql = "SELECT bookings.*, users.first_name, users.rating, 
                users.last_name, users.profile_pic, users.selfie_image 
                FROM bookings LEFT JOIN users ON 
                (bookings.user_id = users.id) WHERE 
                bookings.service_provider_id = '" . $this->userId . "' AND 
                bookings.service_start_time >= '" . $today_start_time_utc . "' AND 
                bookings.service_start_time <= '" . $today_end_time_utc . "' AND                 
                bookings.booking_status = '1'  AND 
                bookings.is_in_progress = 1 AND 
                bookings.is_cancelled = 0  AND 
                bookings.is_orphan_booking = 0 
                ORDER BY service_start_time LIMIT 1";
                
                Log::Info("current_service_sql: " . $current_service_sql);
                $current_service = DB::select($current_service_sql);

                $current_schedule = [];
                if (!empty($current_service)) 
                {
                    $space_details = MySpace::where('id', $current_service[0]->space_id)->first();

                    $user_selfie_image = $current_service[0]->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                
                    if($profile_pic == "")
                    {
                        if (!empty($current_service[0]->profile_pic)) 
                        {
                            $pic_path = explode('/', $current_service[0]->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) 
                            {
                                $profile_pic = $this->get_user_image_path($current_service[0]->profile_pic, 'homeowners');
                            } 
                            else 
                            {
                                $profile_pic = $current_service[0]->profile_pic;
                            }
                        } 
                        else 
                        {
                            $profile_pic = "";
                        }    
                    }    

                    $timezone = $check_user_exists['timezone'];
                    $service_start_time_utc = $current_service[0]->service_start_time;
                    $service_start_time_user = $this->utcTimeToUserTime($service_start_time_utc, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $allStatus = $this->getBookingStatus($current_service[0]);

                    $booking_price = $this->bookingPriceForCleaner($current_service[0]->booking_price);
                    
                    $current_schedule = [
                        'id' => $current_service[0]->id,
                        'user_id' => (string) @$current_service[0]->user_id,
                        'space_id' => (string) @$current_service[0]->space_id,
                        'space_nickname' => (string) @$space_details['name'],
                        'first_name' => (string) @$current_service[0]->first_name,
                        'last_name' => (string) @$current_service[0]->last_name,
                        'profile_pic' => (string) $profile_pic,                        
                        'booking_price' => (string) $booking_price,
                        'stripe_payout_fees' => (string) $this->stripe_fees,
                        'booking_address' => (string) @$space_details['address'],
                        'latitude' => (string) @$space_details['latitude'],
                        'longitude' => (string) @$space_details['longitude'],
                        'booking_frequency' => (string) @$current_service[0]->booking_frequency,
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) @$booking_time,
                        'booking_hours' => (string) @$current_service[0]->booking_hours,
                        'ratings' => (string) $this->ratingFormat(@$current_service[0]->rating),
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'is_cancelled' => $allStatus['is_cancelled'],
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete']
                    ];
                } 
                
                //today schedule 
                //service_start_time need to be with in today start and end time
                $check_user_today_schedule_sql = "SELECT bookings.*, users.first_name, users.last_name, 
                users.profile_pic,users.selfie_image 
                FROM bookings LEFT JOIN users ON 
                (bookings.user_id = users.id) WHERE 
                bookings.service_provider_id = " . $this->userId . " AND 
                bookings.service_start_time >= '" . $today_start_time_utc . "' AND 
                bookings.service_start_time <= '" . $today_end_time_utc . "' AND 
                bookings.booking_status = '1' AND 
                bookings.is_cancelled = 0 
                ORDER BY service_start_time";
                
                Log::info('check_user_today_schedule_sql: '.$check_user_today_schedule_sql);

                $today_scehdules = [];

                $check_user_today_schedule = DB::select($check_user_today_schedule_sql);
                if (!empty($check_user_today_schedule)) {
                    $check_count = count($check_user_today_schedule);
                    foreach ($check_user_today_schedule as $booking_schedule) {
                        $myspace = Myspace::where('id', $booking_schedule->space_id)->first();

                        $user_selfie_image = $booking_schedule->selfie_image;
                        $selfie_image = $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

                        if($profile_pic == "")
                        {
                            if (!empty($booking_schedule->profile_pic)) 
                            {
                                $pic_path = explode('/', $booking_schedule->profile_pic);
                                $path_count = count($pic_path);
                                if ($path_count == 1) 
                                {
                                    $profile_pic = $this->get_user_image_path($booking_schedule->profile_pic, 'homeowners');
                                } 
                                else 
                                {
                                    $profile_pic = $booking_schedule->profile_pic;
                                }
                            } 
                            else 
                            {
                                $profile_pic = "";
                            }
                        }

                        $timezone = $check_user_exists['timezone'];
                        $service_start_time_utc = $booking_schedule->service_start_time;
                        $service_start_time_user = $this->utcTimeToUserTime($service_start_time_utc, $timezone);
                        $booking_time = date("H:i:s", strtotime($service_start_time_user));
                        $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                        $allStatus = $this->getBookingStatus($booking_schedule);
                        $today_scehdules[] = [
                            'id' => (string) @$booking_schedule->id,
                            'user_id' => (string) @$booking_schedule->user_id,
                            'first_name' => (string) @$booking_schedule->first_name,
                            'last_name' => (string) @$booking_schedule->last_name,
                            'profile_pic' => $profile_pic,
                            'space_id' => (string) @$booking_schedule->space_id,
                            'booking_address' => (string) @$myspace['name'],
                            'latitide' => (string) @$myspace['latitude'],
                            'longitude' => (string) @$myspace['longitude'],
                            'booking_frequency' => (string) @$booking_schedule->booking_frequency,
                            'booking_date' => (string) $booking_date_user,
                            'booking_time' => $booking_time,
                            'booking_hours' => (string) @$booking_schedule->booking_hours,
                            'is_upcoming' => $allStatus['is_upcoming'],
                            'is_pending' => $allStatus['is_pending'],
                            'is_in_progress' => $allStatus['is_in_progress'],
                            'is_on_route' => $allStatus['is_on_route'],
                            'is_completed' => $allStatus['is_completed'],
                            'is_cancelled' => $allStatus['is_cancelled'],
                            'pending_start' => $allStatus['pending_start'],
                            'pending_complete' => $allStatus['pending_complete'],
                            'count' => $check_count,
                            'stripe_payout_fees' => (string) $this->stripe_fees,
                        ];
                    }
                } 
                
                $ratings_val = Ratings::where('ratings_for', $this->userId)->orderBy('created_at', 'DESC')->first();

                $notificationStat = Notifications::where(['receiver_id' => $this->userId, 'notification_read' => 0])->first();

                $notifiction_read = 1;
                if (!empty($notificationStat)) 
                {
                    $notifiction_read = 0;
                }

                $response['user_details'] = [
                    'id' => $this->userId,
                    'first_name' => (string) @$check_user_exists['first_name'],
                    'last_name' => (string) @$check_user_exists['last_name'],
                    'profile_pic' => $profile_image,
                    'selfie_image' =>$selfie_image,
                    'is_super_cleaner' => $is_super_cleaner,
                    'is_email_verified' => (string) $check_user_exists['is_email_verified'],
                    'is_phone_number_verified' => (string) @$check_user_exists['is_phone_number_verified'],
                    'authenticate_status' => (string) @$check_user_exists['authenticate_status'],
                    'notifiction_read' => $notifiction_read,
                    'notification_status' => (string) @$check_user_exists['push_notification'],
                    'work_status' => (string) @$check_user_exists['work_status'],
                    'referral_code' => (string) @$check_user_exists['unique_code'],
                    'referral_amount' => (string)env("CLEANER_REFERRAL_SENDER_EARNING"),
                    'referral_description' => env("CLEANER_REFERRAL_DESCRIPTION"),
                    'referral_balance' => (string) $this->amountToFloat(@$check_user_exists['referral_balance']),
                    'ratings' => (string) $this->ratingFormat(@$check_user_exists->rating),
                    'space_status' =>$space_status,
                    'rating_details' => $ratingDetails, 
                ];

                $response['booking_details'] = [
                    'current_scedule' => $current_schedule,
                    'today_scehdule' => $today_scehdules,
                    'service_fees' => $this->charge_deduct_cleaner_total_percent,
                    'stripe_payout_fees' => (string) $this->stripe_fees,
                ];
                $this->success("Listings Found", $response);

                /* Check user is of homeowner type */

            } 
            else if ($user_type == 'homeOwner') 
            {                                                

                $cleaner_available_in_list = $this->getFavCleaners($this->userId);
                
                /* advance favorite cleaner notify checking 
                  if favorite cleaner is selected in addadvanceBookings                 
                  and rejected or keep idel then after send push in cron@sendHomeOwnerFavCleanerNotification API
                  it will show in dashboard to resend other cleaner or cancel booking
                */
                $advanceFavoriteCleaner = [];
                
                $advance_fav_cleaner_notify = 0;

                $checkBookingAdvanceFavCleaner = Bookings::where(['user_id' => $this->userId, 'advance_fav_cleaner_notify' => 1, 'booking_status' => '0','booking_type'=>'advanced'])->first();
                if ($checkBookingAdvanceFavCleaner) 
                {
                    $cleaner_details = Users::where('id', $checkBookingAdvanceFavCleaner['service_provider_id'])->first();
                
                    $cleaner_name = $cleaner_details['first_name']. ' '.$cleaner_details['last_name'];

                    $service_start_time_user = $this->utcTimeToUserTime($checkBookingAdvanceFavCleaner['service_start_time'], $check_user_exists['timezone']);
                    $service_end_time_user = $this->utcTimeToUserTime($checkBookingAdvanceFavCleaner['service_end_time'], $check_user_exists['timezone']);
                    
                    $advance_fav_cleaner_notify = $checkBookingAdvanceFavCleaner['advance_fav_cleaner_notify'];
                    $advanceFavoriteCleaner['booking_id'] = $checkBookingAdvanceFavCleaner['id'];


                    $message = "Sorry your favorite cleaner ".$cleaner_name." is not available on ".date('Y-m-d',strtotime($service_start_time_user))." at ".date('h:i A',strtotime($service_start_time_user)).". Would you like us to provide you with another cleaner?";

                    $advanceFavoriteCleaner['message'] = $message;

                    
                }
               

                /* Ratings part: */
                $ratingDetails = [];
                
                $ratingDetails['is_last_service_rated'] = 1;
                $checkBookingRating = Bookings::where(['user_id' => $this->userId, 'ratingGivenByHomeOwner' => 0, 'is_completed' => 1, 'is_cancelled' => 0,'is_orphan_booking'=>0])->first();
                if ($checkBookingRating) 
                {
                    $ratingDetails['booking_id'] = $checkBookingRating['id'];
                    $ratingDetails['is_last_service_rated'] = 0;
                    
                    $is_fav = 0;
                    $check_favourite_exists = Favourites::where(['user_id' => $this->userId, 'service_provider_id' => $checkBookingRating['service_provider_id']])->first();
                    if (!empty($check_favourite_exists)) 
                    {
                        if ($check_favourite_exists->status == 'favourite') 
                        {
                            $is_fav = 1;
                        }
                    }

                    $ratingDetails['is_fav'] = $is_fav;

                    
                    $rating_cleaner_detail = Users::where(['id' => $checkBookingRating['service_provider_id']])->first();

                    $ratingDetails['booking_type'] = ($rating_cleaner_detail['booking_type'] == 'advanced')?'Scheduled':'Instant';
                    $ratingDetails['cleaner_id'] = $rating_cleaner_detail['id'];
                    $ratingDetails['cleaner_selfie'] = (!empty($rating_cleaner_detail['selfie_image']))?$this->get_authenticate_certificate($rating_cleaner_detail['selfie_image'],'selfie_verification'):'';
                    $ratingDetails['cleaner_first_name'] = (!empty($rating_cleaner_detail['first_name']))?$rating_cleaner_detail['first_name']:'';
                    $ratingDetails['cleaner_last_name'] = (!empty($rating_cleaner_detail['last_name']))?$rating_cleaner_detail['last_name']:'';

                }

                /* Get recent pending or upcoming bookings of homeowner */
                $sql = "SELECT bookings.*, users.first_name as first_name, users.last_name as last_name, 
                users.rating as rating, users.profile_pic as profile_pic, users.selfie_image as selfie_image, 
                users.is_super_cleaner FROM bookings LEFT JOIN users ON 
                (bookings.service_provider_id = users.id) WHERE 
                bookings.user_id = '" . $this->userId . "' AND 
                bookings.service_start_time >= '" . $today_start_time_utc . "' AND 
                bookings.service_start_time <= '" . $today_end_time_utc . "' AND                  
                bookings.is_cancelled = 0 AND 
                bookings.is_completed = 0 AND 
                bookings.is_orphan_booking = 0 
                ORDER BY bookings.service_start_time ASC";
                Log::Info("========================\nUPCOMING BOOKING SQL: " . $sql);

                $values = collect(\DB::select($sql))->all();

                if (!empty($values)) 
                {
                    $price = array_column($values, 'service_start_time');                    
                    array_multisort($price, SORT_ASC, $values);

                    $get_space_name = Myspace::where('id', $values[0]->space_id)->first();

                    $user_selfie_image = (!empty($values[0]->selfie_image))?$values[0]->selfie_image:"";
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                    
                    if($profile_pic == "")
                    {
                        if (!empty($values[0]->profile_pic)) 
                        {
                            $pic_path = explode('/', $values[0]->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) 
                            {
                                $profile_pic = $this->get_user_image_path($values[0]->profile_pic, 'cleaners');
                            } 
                            else 
                            {
                                $profile_pic = $values[0]->profile_pic;
                            }
                        } 
                        else 
                        {
                            $profile_pic = "";
                        }    
                    } 
                    $ratings_val = Ratings::where('ratings_for', $values[0]->service_provider_id)->orderBy('created_at', 'DESC')->first();

                    $timezone = $check_user_exists['timezone'];
                    $service_start_time_utc = $values[0]->service_start_time;
                    $service_start_time_user = $this->utcTimeToUserTime($service_start_time_utc, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $allStatus = $this->getBookingStatus($values[0]);
                    /* Display user latest upcoming booking */

                    $booking_details = [
                        'id' => $values[0]->id,
                        'first_name' => (string) (!empty($values[0]->first_name))?@$values[0]->first_name:"",
                        'last_name' => (string) (!empty($values[0]->first_name))?@$values[0]->last_name:"",
                        'profile_pic' => (string) @$profile_pic,
                        'space_id' => (string) @$values[0]->space_id,
                        'space_nickname' => (string) @$get_space_name['name'],
                        'booking_address' => (string) @$get_space_name['address'],
                        'latitude' => (string) @$get_space_name['latitude'],
                        'longitude' => (string) @$get_space_name['longitude'],                        
                        'booking_frequency' => (string) @$values[0]->booking_frequency,
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) @$booking_time,
                        'booking_hours' => (string) @$values[0]->booking_hours,
                        'is_super_cleaner' => (string) @$values[0]->is_super_cleaner,
                        'ratings' => (string) $this->ratingFormat(@$values[0]->rating),
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'is_cancelled' => $allStatus['is_cancelled'],
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete']
                    ];
                }

                /* Homeowner profile details for dashboard */

                $user_details = [
                    'id' => $this->userId,
                    'first_name' => (string) @$check_user_exists['first_name'],
                    'last_name' => (string) @$check_user_exists['last_name'],
                    'profile_pic' => $profile_image,
                    'selfie_image' => $selfie_image,
                    'is_email_verified' => (string) @$check_user_exists['is_email_verified'],
                    'is_phone_number_verified' => (string) @$check_user_exists['is_phone_number_verified'],
                    'authenticate_status' => (string) @$check_user_exists['authenticate_status'],
                    'notifiction_read' => '1',
                    'notification_status' => (string) @$check_user_exists['push_notification'],
                    'referral_code' => (string) @$check_user_exists['unique_code'],
                    'referral_amount' => (string)env("HOMEOWNER_REFERRAL_SENDER_EARNING"),
                    'referral_description' => env("HOMEOWNER_REFERRAL_DESCRIPTION"),
                    'referral_balance' => (string) $this->amountToFloat(@$check_user_exists['referral_balance']),
                    'rating_details' => $ratingDetails,                    
                    'ratings' => (string) $this->ratingFormat(@$check_user_exists->rating),
                    'service_fees' => $this->skep_percent,
                    'space_status' =>$space_status,
                    'cleaner_available_in_list' =>$cleaner_available_in_list                    
                ];
                if(!empty($advanceFavoriteCleaner))
                {
                    $user_details['advanceFavoriteCleaner'] = $advanceFavoriteCleaner;
                }
                $response['user_details'] = $user_details;
                if (!empty($booking_details)) 
                {
                    $booking_details = $booking_details;
                } 
                else 
                {
                    $booking_details = '';
                }

                $response['booking_details'] = $booking_details;
                $response['service_fees'] = $this->skep_percent;

                Log::info('getDashboardDetails OUTPUT: ' . json_encode($response));

                $this->success("Listings Found", $response);
            }
        } 
        else 
        {
            $this->error("User Not exists");
        }
    }

    /* Function to calculate statistics of cleaner */

    public function getCleanerStats(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'reportFor' => 'required',
                //'week_start_date' => 'required',
                //'week_end_date' => 'required',
            ]
        );
        Log::Info("getCleanerStats: " . json_encode($input));

        $where = ['id' => $this->userId, 'user_type' => 'cleaner'];
        $check_user_exists = Users::where($where)->first();
        $reportFor = $input['reportFor'];
        if ('month' == $reportFor) {
            $monthVal = $input['month'];
            $startDate = date('Y-' . $monthVal . '-1');
            $endDate = date('Y-m-t', strtotime($startDate));
            $count = date('t', strtotime($startDate));
            $nextMonth = $monthVal + 1;
            $fstartDate = date("Y-m-d", strtotime($startDate . ' + 1 month'));
            $fendDate = date('Y-m-t', strtotime($fstartDate));
            $fcount = date('t', strtotime($fstartDate));
        } else {
            $startDate = date("Y-m-d", strtotime($input['week_start_date']));
            $endDate = $input['week_end_date'];
            $fstartDate = date('Y-m-d', strtotime($endDate . ' + 1 days'));
            $fendDate = date('Y-m-d', strtotime($fstartDate . ' + 7 days'));
            $count = 7;
            $fcount = 7;
        }
        /* Check users bookings earnings in a week */

        if (!empty($check_user_exists)) {

            $sql = "SELECT * FROM `bookings` WHERE `service_provider_id` = '" . $this->userId . "' AND date(`service_start_time`) >= '" . $startDate . "' AND date(`service_end_time`) <= '" . $endDate . "' AND booking_status = 1  AND is_completed = 1 AND booking_type = 'instant' AND is_cleaner_paid is not null and is_cancelled = 0 ORDER BY `service_start_time` ASC";
            $check_user_expense = DB::select($sql);
            array_multisort(array_column($check_user_expense, 'service_start_time'), SORT_ASC);
            $result = [];

            /* Add Same day expenses in foreach loop */

            foreach ($check_user_expense as $expenses) {

                $booking_date = date("Y-m-d",strtotime($expenses->service_start_time));
                if (!isset($result[$booking_date])) {
                    $result[$booking_date] = $expenses;
                } else {
                    $result[$booking_date]->amount_paid_cleaner += $expenses->amount_paid_cleaner;
                }
            }

            /* Filter weekday expenses */
            $entered_price_vals = $not_entered_price_vals = [];
            for ($i = 0; $i < $count; $i++) {

                if (array_key_exists($startDate, $result)) {
                    $booking_date = date("Y-m-d",strtotime($result[$startDate]->service_start_time));
                    $entered_price_vals[] = [
                        'booking_date' => $booking_date,
                        'timestamp' => strtotime($booking_date),
                        'booking_price' =>  $result[$startDate]->amount_paid_cleaner,
                    ];
                } else {
                    $not_entered_price_vals[] = [
                        'booking_date' => $startDate,
                        'timestamp' => strtotime($startDate),
                        'booking_price' => 0,
                    ];
                }

                $startDate = date("Y-m-d", strtotime('+1 day', strtotime($startDate)));
            }
            $combine_stats = [];
            if (!empty($entered_price_vals) && !empty($not_entered_price_vals)) {
                $combine_stats = array_merge($entered_price_vals, $not_entered_price_vals);
            } else if (!empty($entered_price_vals) && empty($not_entered_price_vals)) {
                $combine_stats = $entered_price_vals;
            } else if (!empty($entered_price_vals) && empty($entered_price_vals)) {
                $combine_stats = $not_entered_price_vals;
            }

            /* Upcoming Values Amount */
            $sql = "SELECT * FROM `bookings` WHERE `service_provider_id` = '" . $this->userId . "' AND date(`service_start_time`) >= '" . $fstartDate . "' AND date(`service_end_time`) <= '" . $fendDate . "' AND booking_status = 1  AND booking_type = 'advanced' and is_cancelled = 0 ORDER BY `service_start_time` ASC";
            $upcoming_values = DB::select($sql);
            array_multisort(array_column($upcoming_values, 'service_start_time'), SORT_ASC);
            $upcomingResult = [];

            $bookings = new Bookings();
            /* Add Same day expenses in foreach loop */
            $payablePrice = 0;
            foreach ($upcoming_values as $upcoming) {
                $booking_date = date("Y-m-d",strtotime($upcoming->service_start_time));
                if (!isset($upcomingResult[$booking_date])) {
                   
                    $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($upcoming->booking_price);
                    $payablePrice += $this->amountToFloat($payPriceWithTaxes['amt']) * 100;
                    $upcoming->booking_price = $payablePrice;
                    $upcomingResult[$booking_date] = $upcoming; //echo "<pre>"; print_r($upcomingResult); die;
                } else {
                    
                    $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($upcoming->booking_price);
                    $payablePrice += $this->amountToFloat($payPriceWithTaxes['amt']) * 100;
                    $upcoming->booking_price = $payablePrice;
                    $upcomingResult[$booking_date] = $upcoming;

                }
            }

            Log::Info("getCleanerStats upcomingResult: " . json_encode($upcomingResult));
            /* Filter weekday expenses */
            $fentered_price_vals = $fnot_entered_price_vals = [];
            for ($i = 0; $i < $fcount; $i++) {

                if (array_key_exists($fstartDate, $upcomingResult)) {
                    $booking_date = date("Y-m-d",strtotime($upcomingResult[$fstartDate]->service_start_time));
                    $fentered_price_vals[] = [
                        'booking_date' => $booking_date,
                        'timestamp' => strtotime($booking_datebooking_date),
                        'booking_price' => $this->amountToFloat($upcomingResult[$fstartDate]->booking_price),
                    ];
                } else {
                    $fnot_entered_price_vals[] = [
                        'booking_date' => $fstartDate,
                        'timestamp' => strtotime($fstartDate),
                        'booking_price' => 0,
                    ];
                }

                $fstartDate = date("Y-m-d", strtotime('+1 day', strtotime($fstartDate)));
            }
            $fcombine_stats = [];
            if (!empty($fentered_price_vals) && !empty($fnot_entered_price_vals)) {
                $fcombine_stats = array_merge($fentered_price_vals, $fnot_entered_price_vals);
            } else if (!empty($fentered_price_vals) && empty($fnot_entered_price_vals)) {
                $fcombine_stats = $fentered_price_vals;
            } else if (!empty($fentered_price_vals) && empty($fentered_price_vals)) {
                $fcombine_stats = $fnot_entered_price_vals;
            }
            /* Set statistics in weekdays */

            if (!empty($combine_stats)) {

                foreach ($combine_stats as $statistics) {
                  
                    $earning_values[] = [
                        'date' => $statistics['booking_date'],
                        'timestamp' => $statistics['timestamp'],
                        'booking_price' => $this->amountToFloat($statistics['booking_price']),
                    ];
                }
                foreach ($fcombine_stats as $fstatistics) {
                    $fearning_values[] = [
                        'date' => $fstatistics['booking_date'],
                        'timestamp' => $fstatistics['timestamp'],
                        'booking_price' => $this->amountToFloat($fstatistics['booking_price']),
                    ];
                }
                array_multisort(array_column($earning_values, 'timestamp'), SORT_ASC, $earning_values);
                array_multisort(array_column($fearning_values, 'timestamp'), SORT_ASC, $fearning_values);
                $get_price = array_sum(array_column($earning_values, 'booking_price'));
                $results_vals['current_earnings'] = (string) $this->amountToFloat($earning_values);
                $results_vals['total_amount'] = (string) $this->amountToFloat($get_price);
                $results_vals['forcast_earnings'] = (string) $this->amountToFloat($fearning_values);
                $results_vals['ratings'] = (string) $this->ratingFormat(@$check_user_exists->rating);

                Log::Info("getCleanerStats results_vals: " . json_encode($results_vals));

                $this->success("Weekly Stats Found", $results_vals);
            } else {
                $this->error("No Earnings Found");
            }
        } else {
            $this->error("User Not Exists");
        }
    }

    /* Function to get cleaner schedule details */

    public function getCleanerSchedule(Request $request)
    {
        $input = $request->all();
        
        $this->validation(
            $request->all(),
            [
                'date' => 'required',
                'display_slot' => 'required',
            ]
        );

        Log::Info("getCleanerSchedule INPUT: " . json_encode($input));

        $date = $input['date'];
        $display_slot = $input['display_slot'];                                       
        
        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) {

            $user_type = $check_user_exists['user_type'];            
            $timezone = $check_user_exists['timezone'];

            $today_start_time_user = date("Y-m-d 00:00:00",strtotime($date));
            $today_end_time_user = date("Y-m-d 23:59:59",strtotime($date));

            $today_start_time_utc = $this->userTimeToUTCTime($today_start_time_user, $timezone);
            $today_end_time_utc = $this->userTimeToUTCTime($today_end_time_user, $timezone);            
            
            $space_id = (!empty($input['space_id'])) ? $input['space_id'] : '';

            $booked_dates = $this->returnBookedDates($date, $display_slot,$space_id,$user_type,$timezone);

            $sql = "SELECT bookings.*, users.first_name, users.last_name, users.profile_pic,users.selfie_image 
            FROM bookings LEFT JOIN users ON 
            (bookings.user_id = users.id) WHERE 
            bookings.service_provider_id = '" . $this->userId . "' AND 
            bookings.service_start_time >= '" . $today_start_time_utc . "' AND 
            bookings.service_start_time <= '" . $today_end_time_utc . "' AND 
            bookings.booking_status = '1' AND 
            bookings.is_orphan_booking = 0 AND  
            bookings.is_cancelled = 0 
            ORDER BY service_start_time ASC"; 

            $bookings_time = DB::select($sql);
            $cleaning_schedules = [];
            $total_services = 0;
            if (!empty($bookings_time)) {
                $total_services = count($bookings_time);
                foreach ($bookings_time as $bookings) {

                    $user_selfie_image = $bookings->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                                        
                    if($profile_pic == "")
                    {
                        if (!empty($bookings->profile_pic)) {
                            $pic_path = explode('/', $bookings->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) {
                                $profile_pic = $this->get_user_image_path($bookings->profile_pic, 'homeowners');
                            } else {
                                $profile_pic = $bookings->profile_pic;
                            }
                        } else {
                            $profile_pic = "";
                        }    
                    } 

                    $get_space_name = MySpace::where('id', $bookings->space_id)->first();

                    $timezone = $check_user_exists['timezone'];

                    $service_start_time_user = $this->utcTimeToUserTime($bookings->service_start_time, $timezone);
                    $service_end_time_user = $this->utcTimeToUserTime($bookings->service_end_time, $timezone);
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $cleaning_schedules[] = [
                        'id' => (int) $bookings->id,
                        'first_name' => (string) @$bookings->first_name,
                        'last_name' => (string) @$bookings->last_name,
                        'profile_pic' => (string) @$profile_pic,
                        'space_id' => (string) @$bookings->space_id,
                        'space_nickname' => (string) @$get_space_name['name'],
                        'booking_address' => (string) @$get_space_name['address'],
                        'latitude' => (string) @$get_space_name['latitude'],
                        'longitude' => (string) @$get_space_name['longitude'],
                        'booking_frequency' => (string) @$bookings->booking_frequency,
                        'booking_date' => (string) $booking_date_user,
                        'booking_start_time' => (string) @date("H:i:s", strtotime($service_start_time_user)),
                        'booking_end_time' => (string) @date("H:i:s", strtotime(@$service_end_time_user)),
                        'booking_hours' => (string) @$bookings->booking_hours,
                        'is_super_cleaner' => (string) @$bookings->is_super_cleaner,
                        'ratings' => (string) $this->ratingFormat(@$check_user_exists->rating),
                        'on_route' => '0',
                    ];
                }
                
            }
            
            $start_date = date("Y-m-d 00:01:01");


            // selected date completed number of services
            $completed_sql = "SELECT bookings.*, users.first_name, users.last_name, users.profile_pic FROM bookings 
            LEFT JOIN users ON (bookings.user_id = users.id) WHERE 
            bookings.service_provider_id = '" . $this->userId . "' AND 
            bookings.service_start_time >= '" . $today_start_time_utc . "' AND 
            bookings.service_start_time <= '" . $today_end_time_utc . "' AND 
            bookings.booking_status = '1' AND 
            bookings.is_completed = 1 AND 
            bookings.is_cancelled = 0 AND  
            bookings.is_orphan_booking = 0 
            ORDER BY service_start_time";

            $completed_services = DB::select($completed_sql);
            $complete_service_total = count($completed_services);


            // selected date upcoming number of services
            $upcoming_sql = "SELECT bookings.*, users.first_name, users.last_name, users.profile_pic FROM bookings 
            LEFT JOIN users ON (bookings.user_id = users.id) WHERE 
            bookings.service_provider_id = '" . $this->userId . "' AND 
            bookings.service_start_time >= '" . $today_start_time_utc . "' AND 
            bookings.service_start_time <= '" . $today_end_time_utc . "' AND 
            bookings.booking_status = '1' AND 
            bookings.is_completed = 0 AND 
            bookings.is_cancelled = 0 AND 
            bookings.is_orphan_booking = 0 
            ORDER BY service_start_time";

            $upcoming_services = DB::select($upcoming_sql);
            $upcoming_service_total = count($upcoming_services);

            $response = [
                'date' => $date,
                'total_services' => $total_services,
                'services_time' => $cleaning_schedules,
                'completed_services' => $complete_service_total,
                'upcoming_services' => $upcoming_service_total,
                'booked_dates'=>$booked_dates
            ];
            $this->success("Bookings Found", $response);
        } else {
            $this->error("User Not Exists");
        }
    }

    /* Function to get calendar dates that are booked */
    public function returnBookedDates($date, $display_slot,$space_id,$user_type,$timezone)
    {
        $id_values = [];
        if($display_slot == 'week')
        {
            list($start_date, $end_date) = $this->x_week_range($date);
            $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
            $end_date = date('Y-m-d 23:59:59', strtotime($end_date));                

        }
        else
        {                
            $start_date = date("Y-m-01 00:00:00", strtotime($date));
            $end_date = date("Y-m-t 23:59:59", strtotime($date));
            
        }

        $start_date = $this->userTimeToUTCTime($start_date, $timezone);
        $end_date = $this->userTimeToUTCTime($end_date, $timezone);

        // booking_status = '1' not adding to show pending booking (booking_status='0') as well for 
        // home owner end. For cleaner end booking always booking_status = '1' so no need to add condition
        $sql = "SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '".$start_date."' AND
                `service_start_time` <= '".$end_date."'  AND 
                is_orphan_booking = 0 ";
                    
        if($user_type == 'homeOwner')
        {
            $sql .=  " AND user_id = '".$this->userId."' ";
        }
        else
        {
            $sql .=  " AND service_provider_id = '".$this->userId."' ";
        }

        if (!empty($space_id)) {
            $sql .=  " AND space_id = '".$space_id."' ";
        }
        $sql .=  " ORDER BY booking_date ASC";
        Log::Info("\n sql : ".$sql);
        $check_booking_date = DB::select($sql);
        
        if (!empty($check_booking_date)) {
            
            foreach ($check_booking_date as $bookings_date) {
                $service_start_time_user = $this->utcTimeToUserTime($bookings_date->service_start_time, $timezone);
                $values[] = [
                    'booking_id' => $bookings_date->id,
                    'busy_dates' => date("Y-m-d", strtotime($service_start_time_user)),
                ];
            }

            /* Sort the bookings ids */

            array_multisort($values, SORT_ASC);
            $busy_dates = array_unique(array_column($values, 'busy_dates'));
            $busy_dates_ids = array_intersect_key($values, $busy_dates);
            
            Log::Info("=========================\n busy_dates: " . json_encode($busy_dates));
            Log::Info("=========================\n values: " . json_encode($values));
            Log::Info("=========================\nbusy_dates_ids: " . json_encode($busy_dates_ids));

            $busy_dates_ids = $values;
            Log::Info("=========================\n LAST busy_dates_ids: " . json_encode($busy_dates_ids));

            if (!empty($busy_dates_ids)) {
                foreach ($busy_dates_ids as $busy_ids) {

                   // $booking_detail = $this->getBusyBookingDetail($busy_ids['booking_id']);
                    $id_values[] = [
                        'id' => $busy_ids['booking_id'],
                      //  'booking_detail' => $booking_detail,
                        'booking_date' => $busy_ids['busy_dates'],
                    ];
                }
               
            }
        } 
        return $id_values;

    }
    public function getBookedDates(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'date' => 'required',
                'display_slot' => 'required',
            ]
        );

        Log::Info("=========================\ngetBookedDates INPUT: " . json_encode($input));

        $date = $input['date'];
        $display_slot = $input['display_slot'];

        $where = ['id' => $this->userId, 'account_blocked' => 0];
        $check_user_exists = Users::where($where)->first();
        if (!empty($check_user_exists)) {
            $user_type = $check_user_exists['user_type'];            
            $timezone = $check_user_exists['timezone'];
            $space_id = (!empty($input['space_id'])) ? $input['space_id'] : '';

            $id_values = $this->returnBookedDates($date, $display_slot,$space_id,$user_type,$timezone);
            
            $this->success("Busy Dates Found", $id_values);

        } else {
            $this->error("User Not Exists");
        }
    }

    /* Function to get all booking details */

    public function getBookingDetails(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
            ]
        );

        Log::info('getBookingDetails  PARAM: ' . json_encode($input));

        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) {

            $timezone = $check_user_exists['timezone'];
            Log::info('TIMEZONE: user: ' . $this->userId . " : " . $timezone);
            /* Check user is homeowner */

            if ($check_user_exists['user_type'] == 'homeOwner') 
            {
                $check_booking_sql = "SELECT bookings.*, users.first_name, users.last_name, users.rating as rating, users.profile_pic as profile_pic,users.selfie_image as selfie_image, users.phone_number as phone_number, users.latitude as user_latitude, users.longitude as user_longitude, users.qb_id as qb_id, users.is_super_cleaner FROM bookings 
                LEFT JOIN users ON bookings.service_provider_id = users.id WHERE 
                bookings.id = " . $input['booking_id'];
                $check_booking_exist = DB::select($check_booking_sql);

                if (!empty($check_booking_exist)) 
                {
                    $booking_status = $check_booking_exist[0]->booking_status;

                    /* Check image is normal or social profile pic */
                    $user_selfie_image = $check_booking_exist[0]->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                    
                    if($profile_pic == "")
                    {
                        if (!empty($check_booking_exist[0]->profile_pic)) 
                        {
                            $pic_path = explode('/', $check_booking_exist[0]->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) 
                            {
                                $profile_pic = $this->get_user_image_path($check_booking_exist[0]->profile_pic, 'cleaners');
                            } 
                            else 
                            {
                                $profile_pic = $check_booking_exist[0]->profile_pic;
                            }
                        } 
                        else 
                        {
                            $profile_pic = "";
                        }    
                    } 

                    $ratings = Ratings::where('ratings_for', $check_booking_exist[0]->service_provider_id)->orderBy('created_at', 'DESC')->first();

                    $get_space_details = Myspace::where('id', $check_booking_exist[0]->space_id)->first();
                    $services = $check_booking_exist[0]->booking_services;
                    if (!empty($services)) 
                    {
                        $service_values = (explode(",", $services));
                    } 
                    else 
                    {
                        $service_values = [];
                    }
                    $services_vals = [];
                    $count_services = count($service_values);

                    /* Check if the booking has any extra service or not */

                    if ($count_services == 0) 
                    {
                        $service_vals = [];

                        /* Check if the booking has more then 1 extra service */

                    } 
                    else if ($count_services == 1) 
                    {
                        $get_working_time = Extraservices::where('name', $service_values[0])->first();
                        $service_vals[] = [
                            'service_name' => $service_values[0],
                            'service_working_time' => ($get_working_time['time']) ? $get_working_time['time'] : "",
                        ];

                        /* Check if the booking has only one extra service */

                    } 
                    else 
                    {
                        foreach ($service_values as $services) 
                        {
                            $get_working_time = Extraservices::where('name', $services)->first();
                            $service_vals[] = [
                                'service_name' => $services,
                                'service_working_time' => ($get_working_time['time']) ? $get_working_time['time'] : "",
                            ];
                        }
                    }
                    $card = [];
                    $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
                    if ($stripeUserDetails) {
                        $card = $stripeUserDetails->token != null ? unserialize($stripeUserDetails->token) : [];
                        if ($card) 
                        {
                            // $cardDetails =  $charges['payment_method_details']['card'];
                            // $card['brand'] = $cardDetails['brand'];
                            // $card['country'] = $cardDetails['country'];
                            // $card['exp_month'] = $cardDetails['exp_month'];
                            // $card['exp_year'] = $cardDetails['exp_year'];
                            // $card['funding'] = $cardDetails['funding'];
                            // $card['last4'] = $cardDetails['last4'];
                        }
                    }
                    Log::Info("Get Booking STA: " . json_encode($check_booking_exist[0]));
                    $allStatus = $this->getBookingStatus($check_booking_exist[0]);

                    $service_start_time_user = $this->utcTimeToUserTime($check_booking_exist[0]->service_start_time, $timezone);
                    $service_end_time_user = $this->utcTimeToUserTime($check_booking_exist[0]->service_end_time, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_end_time = date("H:i:s", strtotime($service_end_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $response = [
                        'id' => (int) $check_booking_exist[0]->id,
                        'user_id' => (string) @$check_booking_exist[0]->service_provider_id,
                        'first_name' => (string) @$check_booking_exist[0]->first_name,
                        'last_name' => (string) @$check_booking_exist[0]->last_name,
                        'profile_pic' => (string) @$profile_pic,
                        'qb_id' => (string) @$check_booking_exist[0]->qb_id,
                        'phone_number' => (string) @$check_booking_exist[0]->phone_number,
                        'ratings' => (string) $this->ratingFormat(@$check_booking_exist[0]->rating),
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) $booking_time,
                        'booking_end_time' => (string) $booking_end_time,
                        'extra_booking_services' => $service_vals,
                        'cleaner_status' => '',
                        'booking_frequency' => (string) @$check_booking_exist[0]->booking_frequency,
                        'booking_address' => (string) @$check_booking_exist[0]->booking_address,
                        'latitude' => (string) @$check_booking_exist[0]->latitude,
                        'longitude' => (string) @$check_booking_exist[0]->longitude,
                        'user_latitude' => (string) @$check_booking_exist[0]->user_latitude,
                        'user_longitude' => (string) @$check_booking_exist[0]->user_longitude,
                        'space_id' => (string) @$check_booking_exist[0]->space_id,                        
                        'space_latitude' => (string) @$get_space_details['latitude'],
                        'space_longitude' => (string) @$get_space_details['longitude'],                        
                        'space_name' => (string) @$get_space_details->name,
                        'space_address' => (string) @$get_space_details->address,
                        'is_super_cleaner' => (string) @$get_space_details->is_super_cleaner,
                        'unit_number' => (string) @$get_space_details->unit_number,
                        'buzz_number' => (string) @$get_space_details->buzz_number,
                        'special_instructions' => (string) @$check_booking_exist[0]->special_instructions,
                        'bedrooms' => (string) @$get_space_details->bedrooms,
                        'bathrooms' => (string) @$get_space_details->bathrooms,
                        'dens' => (string) @$get_space_details->dens,
                        'booking_hours' => (string) @$check_booking_exist[0]->booking_hours,
                        'booking_price' => (string) $this->amountToFloat(@$check_booking_exist[0]->booking_price),
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete'],
                        'is_cancelled' => (string) @$check_booking_exist[0]->is_cancelled,
                        'booking_type' => $check_booking_exist[0]->booking_type,
                        'service_fees' => $this->skep_percent,
                        'stripe_payout_fees' => '0',
                        'stripe_paid_status' => (string) @$check_booking_exist[0]->paid_status,
                        'stripe_transaction_id' => (string) @$check_booking_exist[0]->transaction_id,
                        'stripe_amount_paid' => (string)  $this->amountToFloat(@$check_booking_exist[0]->amount_paid),
                        'stripe_charge_id' => (string) @$check_booking_exist[0]->charge_id,
                        'cardDetails' => $card,
                        'booking_status' => (string) @$check_booking_exist[0]->booking_status,
                        'skep_percent' => $this->skep_percent,
                        'job_id' => (string) @$check_booking_exist[0]->job_id,
                       
                    ];
                    if((int)$check_booking_exist[0]->is_cancelled == 1 && $check_booking_exist[0]->cancel_amount > 0)
                    {
                    
                        $response['stripe_refund_amount'] = (string) $this->amountToFloat($check_booking_exist[0]->cancel_amount);
                    }
                    if((int)$check_booking_exist[0]->referral_discount > 0)
                    {
                    
                        $response['referral_credit'] = (string) $this->amountToFloat(@$check_booking_exist[0]->referral_discount);
                    }
                    Log::info('BOOKING RESPONSE: ' . json_encode($response));

                    $this->success("Booking Found", $response);
                } 
                else 
                {
                    $this->error("Booking Not Found");
                }
                /* Check if user has of cleaner type */

            } 
            else if ($check_user_exists['user_type'] == 'cleaner') 
            {
                $check_booking_sql = "SELECT bookings.*, users.first_name, users.last_name, users.profile_pic as profile_pic, users.selfie_image as selfie_image, users.phone_number as phone_number, users.latitude as user_latitude, users.longitude as user_longitude, users.qb_id as qb_id FROM bookings 
                LEFT JOIN users ON bookings.user_id = users.id WHERE 
                bookings.id = " . $input['booking_id'];

                $check_booking_exist = DB::select($check_booking_sql);

                if (!empty($check_booking_exist)) 
                {
                    $booking_status = $check_booking_exist[0]->booking_status;

                    /* Check image is normal or social profile pic */
                    $user_selfie_image = $check_booking_exist[0]->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

                    if($profile_pic == "")
                    {
                        if (!empty($check_booking_exist[0]->profile_pic)) 
                        {
                            $pic_path = explode('/', $check_booking_exist[0]->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) 
                            {
                                $profile_pic = $this->get_user_image_path($check_booking_exist[0]->profile_pic, 'homeowners');
                            } 
                            else 
                            {
                                $profile_pic = $check_booking_exist[0]->profile_pic;
                            }
                        } 
                        else 
                        {
                            $profile_pic = "";
                        }    
                    }
                    $get_space_details = Myspace::where('id', $check_booking_exist[0]->space_id)->first();
                    $services = $check_booking_exist[0]->booking_services;
                    if (!empty($services)) 
                    {
                        $service_values = (explode(",", $services));
                    } 
                    else 
                    {
                        $service_values = [];
                    }
                    $services_vals = [];
                    $count_services = count($service_values);
                    if ($count_services == 0) 
                    {
                        $service_vals[] = [];
                    } 
                    else if ($count_services == 1) 
                    {
                        $get_working_time = Extraservices::where('name', $service_values[0])->first();
                        $service_vals[] = [
                            'service_name' => $service_values[0],
                            'service_working_time' => ($get_working_time['time']) ? $get_working_time['time'] : "",
                        ];
                    } 
                    else 
                    {
                        foreach ($service_values as $services) 
                        {
                            $get_working_time = Extraservices::where('name', $services)->first();
                            $service_vals[] = [
                                'service_name' => $services,
                                'service_working_time' => ($get_working_time['time']) ? $get_working_time['time'] : "",
                            ];
                        }
                    }

                    Log::Info("CLEANER getBookingStatus: " . json_encode($check_booking_exist[0]));

                    $allStatus = $this->getBookingStatus($check_booking_exist[0]);

                    $service_start_time_user = $this->utcTimeToUserTime($check_booking_exist[0]->service_start_time, $timezone);
                    $service_end_time_user = $this->utcTimeToUserTime($check_booking_exist[0]->service_end_time, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_end_time = date("H:i:s", strtotime($service_end_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $booking_price = $this->bookingPriceForCleaner($check_booking_exist[0]->booking_price);

                    $response = [
                        'id' => (int) $check_booking_exist[0]->id,
                        'user_id' => (string) @$check_booking_exist[0]->user_id,
                        'first_name' => (string) @$check_booking_exist[0]->first_name,
                        'last_name' => (string) @$check_booking_exist[0]->last_name,
                        'profile_pic' => (string) @$profile_pic,
                        'qb_id' => (string) @$check_booking_exist[0]->qb_id,
                        'phone_number' => (string) @$check_booking_exist[0]->phone_number,
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) $booking_time,
                        'booking_end_time' => (string) $booking_end_time,
                        'extra_booking_services' => $service_vals,
                        'cleaner_status' => '',
                        'booking_frequency' => (string) @$check_booking_exist[0]->booking_frequency,
                        'booking_address' => (string) @$check_booking_exist[0]->booking_address,
                        'latitude' => (string) @$check_booking_exist[0]->latitude,
                        'longitude' => (string) @$check_booking_exist[0]->longitude,
                        'user_latitude' => (string) @$check_booking_exist[0]->user_latitude,
                        'user_longitude' => (string) @$check_booking_exist[0]->user_longitude,
                        'space_id' => (string) @$check_booking_exist[0]->space_id,                        
                        'space_latitude' => (string) @$get_space_details['latitude'],
                        'space_longitude' => (string) @$get_space_details['longitude'],                        
                        'space_name' => (string) @$get_space_details->name,
                        'space_address' => (string) @$get_space_details->address,
                        'unit_number' => (string) @$get_space_details->unit_number,
                        'buzz_number' => (string) @$get_space_details->buzz_number,
                        'special_instructions' => (string) @$check_booking_exist[0]->special_instructions,
                        'bedrooms' => (string) @$get_space_details->bedrooms,
                        'bathrooms' => (string) @$get_space_details->bathrooms,
                        'dens' => (string) @$get_space_details->dens,
                        'booking_hours' => (string) @$check_booking_exist[0]->booking_hours,
                        'booking_price' => (string) $booking_price,
                        'booking_type' => $check_booking_exist[0]->booking_type,
                        'service_fees' => $this->charge_deduct_cleaner_total_percent,
                        'stripe_payout_fees' => $this->stripe_fees,
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete'],
                        'is_cancelled' => (string) @$check_booking_exist[0]->is_cancelled,
                        'booking_status' => (string) @$check_booking_exist[0]->booking_status,
                        'ratings' => (string) $this->ratingFormat(@$check_user_exists->rating),
                        'job_id' => (string) @$check_booking_exist[0]->job_id,
                    ];
                    Log::info('BOOKING RESPONSE: ' . json_encode($response));
                    $this->success("Booking Found", $response);
                } 
                else 
                {
                    $this->error("Booking Not Found");
                }
            }
        } 
        else 
        {
            $this->error("User Not exists");
        }
    }

    public function getBusyBookingDetail($booking_id)
    {            
    
        $results = [];
        $check_user_exists = Users::where('id', $this->userId)->first();      
        
        $user_type = $check_user_exists['user_type'];
        if ($user_type == 'cleaner') {
    
            $sql = "SELECT bookings.*, users.first_name, users.last_name, users.profile_pic, users.selfie_image, users.is_super_cleaner, users.is_phone_number_verified 
            FROM bookings LEFT JOIN users ON (bookings.user_id = users.id) WHERE 
            bookings.id = '" . $booking_id . "'";
            $get_homeowner_details = DB::select($sql);
            if (!empty($get_homeowner_details)) {
    
                $user_selfie_image = $get_homeowner_details[0]->selfie_image;
                $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                
                if($profile_pic == "")
                {
                    if (!empty($get_homeowner_details[0]->profile_pic)) {
                        $pic_path = explode('/', $get_homeowner_details[0]->profile_pic);
                        $path_count = count($pic_path);
                        if ($path_count == 1) {
                            $profile_pic = $this->get_user_image_path($get_homeowner_details[0]->profile_pic, 'homeowners');
                        } else {
                            $profile_pic = $get_homeowner_details[0]->profile_pic;
                        }
                    } else {
                        $profile_pic = "";
                    }    
                }
    
                $allStatus = $this->getBookingStatus($get_homeowner_details[0]);
                $get_space_name = Myspace::where('id', $get_homeowner_details[0]->space_id)->first();
    
                $timezone = $check_user_exists['timezone'];
                $service_start_time_user = $this->utcTimeToUserTime($get_homeowner_details[0]->service_start_time, $timezone);
                $booking_time = date("H:i:s", strtotime($service_start_time_user));
                $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));
    
                $results = [
                    'id' => $get_homeowner_details[0]->id,
                    'user_id' => $get_homeowner_details[0]->user_id,
                    'first_name' => (string) @$get_homeowner_details[0]->first_name,
                    'last_name' => (string) @$get_homeowner_details[0]->last_name,
                    'profile_pic' => (string) @$profile_pic,
                    'space_id' => (string) @$get_homeowner_details[0]->space_id,
                    'space_nickname' => (string) @$get_space_name['name'],
                    'booking_address' => (string) @$get_space_name['address'],
                    'latitude' => (string) @$get_space_name['latitude'],
                    'longitude' => (string) @$get_space_name['longitude'],
                    'booking_frequency' => (string) @$get_homeowner_details[0]->booking_frequency,
                    'booking_date' => (string) $booking_date_user,
                    'booking_time' => (string) @$booking_time,
                    'booking_hours' => (string) @$get_homeowner_details[0]->booking_hours,
                    'is_super_cleaner' => (string) @$get_homeowner_details[0]->is_super_cleaner,
                    'is_upcoming' => $allStatus['is_upcoming'],
                    'is_pending' => $allStatus['is_pending'],
                    'is_on_route' => $allStatus['is_on_route'],
                    'is_completed' => $allStatus['is_completed'],
                    'is_in_progress' => $allStatus['is_in_progress'],
                    'is_cancelled' => $get_homeowner_details[0]->is_cancelled,
                    'pending_start' => $allStatus['pending_start'],
                    'pending_complete' => $allStatus['pending_complete']
    
                ];
                
            } 
        } else if ($user_type == 'homeOwner') {
            
            $date = date("Y-m-d H:i:s");
    
            $sql = "SELECT bookings.*, users.first_name, users.last_name, users.rating as rating, users.profile_pic,users.selfie_image, users.is_super_cleaner, users.is_phone_number_verified 
            FROM bookings LEFT JOIN users ON (bookings.service_provider_id = users.id) WHERE bookings.id = " . $booking_id;
            $get_homeowner_details = DB::select($sql);
            if (!empty($get_homeowner_details)) {
    
                $allStatus = $this->getBookingStatus($get_homeowner_details[0]);
    
                $user_selfie_image = $get_homeowner_details[0]->selfie_image;
                $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
    
                if($profile_pic == "")
                {
                    if (!empty($get_homeowner_details[0]->profile_pic)) {
                        $pic_path = explode('/', $get_homeowner_details[0]->profile_pic);
                        $path_count = count($pic_path);
                        if ($path_count == 1) {
                            $profile_pic = $this->get_user_image_path($get_homeowner_details[0]->profile_pic, 'cleaners');
                        } else {
                            $profile_pic = $get_homeowner_details[0]->profile_pic;
                        }
                    } else {
                        $profile_pic = "";
                    }    
                }
    
                $get_space_name = Myspace::where('id', $get_homeowner_details[0]->space_id)->first();
                $ratings = Ratings::where('ratings_for', $get_homeowner_details[0]->service_provider_id)->first();
    
                $timezone = $check_user_exists['timezone'];
                $service_start_time_user = $this->utcTimeToUserTime($get_homeowner_details[0]->service_start_time, $timezone);
                $booking_time = date("H:i:s", strtotime($service_start_time_user));
                $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));
    
                $results = [
                    'id' => $get_homeowner_details[0]->id,
                    'user_id' => $get_homeowner_details[0]->service_provider_id,
                    'first_name' => (string) @$get_homeowner_details[0]->first_name,
                    'last_name' => (string) @$get_homeowner_details[0]->last_name,
                    'profile_pic' => (string) @$profile_pic,
                    'space_id' => (string) @$get_homeowner_details[0]->space_id,
                    'space_nickname' => (string) @$get_space_name['name'],
                    'booking_address' => (string) @$get_space_name['address'],
                    'latitude' => (string) @$get_space_name['latitude'],
                    'longitude' => (string) @$get_space_name['longitude'],
                    'booking_frequency' => (string) @$get_homeowner_details[0]->booking_frequency,
                    'booking_date' => (string) $booking_date_user,
                    'booking_time' => (string) $booking_time,
                    'booking_hours' => (string) @$get_homeowner_details[0]->booking_hours,
                    'is_super_cleaner' => (string) @$get_homeowner_details[0]->is_super_cleaner,
                    'ratings' => (string) $this->ratingFormat(@$get_homeowner_details[0]->rating),
                    'is_upcoming' => $allStatus['is_upcoming'],
                    'is_pending' => $allStatus['is_pending'],
                    'is_on_route' => $allStatus['is_on_route'],
                    'is_completed' => $allStatus['is_completed'],
                    'is_in_progress' => $allStatus['is_in_progress'],
                    'is_cancelled' => $get_homeowner_details[0]->is_cancelled,
                    'pending_start' => $allStatus['pending_start'],
                    'pending_complete' => $allStatus['pending_complete']
                ];
              
            } 
        }
        
        return $results;
        
    }
    /* Get the busy dates with booking ids in calendar */

    public function checkBusyBookingFromCalendar(Request $request)
    {
        $results = [];
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_date' => 'required',
             //   'timezone' => 'required',
            ]
        );
        
        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) {
            $user_type = $check_user_exists['user_type'];
            $timezone = $check_user_exists['timezone'];
            
            $start_date = date('Y-m-d 00:00:00', strtotime($input['booking_date']));
            $end_date = date('Y-m-d 23:59:59', strtotime($input['booking_date']));                

            $start_date = $this->userTimeToUTCTime($start_date, $timezone);
            $end_date = $this->userTimeToUTCTime($end_date, $timezone);

            $sql = "SELECT bookings.*, 
            user.first_name as user_first_name, 
            user.last_name  as user_last_name, 
            user.profile_pic  as user_profile_pic, 
            user.selfie_image as user_selfie_image, 
            user.rating as user_rating, 
            cleaner.first_name as cleaner_first_name, 
            cleaner.last_name  as cleaner_last_name, 
            cleaner.profile_pic  as cleaner_profile_pic, 
            cleaner.selfie_image as cleaner_selfie_image, 
            cleaner.rating as cleaner_rating, 
            cleaner.is_super_cleaner as cleaner_is_super_cleaner                     
            FROM `bookings` bookings
            LEFT JOIN users user ON (bookings.user_id = user.id) 
            LEFT JOIN users cleaner ON (bookings.service_provider_id = cleaner.id) 
            WHERE 
            bookings.`service_start_time` >= '".$start_date."' AND
            bookings.`service_start_time` <= '".$end_date."'  AND 
            bookings.`is_orphan_booking` = 0 ";
                    
            if($user_type == 'homeOwner')
            {
                $sql .=  " AND bookings.user_id = '".$this->userId."' ";
            }
            else
            {
                $sql .=  " AND bookings.service_provider_id = '".$this->userId."' ";
            }

            $sql .=  " ORDER BY bookings.booking_date ASC";
            Log::Info("\n sql : ".$sql);
            $check_booking_date = DB::select($sql);
            
            if (!empty($check_booking_date)) {
                
                foreach ($check_booking_date as $bookings_date) {
                   
                    
                    if($user_type == 'homeOwner')
                    {
                        $user_selfie_image = $bookings_date->cleaner_selfie_image;
                        $profile_pic = $bookings_date->cleaner_profile_pic;
                        $type = 'cleaners';
                    }
                    else
                    {
                        $user_selfie_image = $bookings_date->user_selfie_image;
                        $profile_pic = $bookings_date->user_profile_pic;
                        $type = 'homeowners';
                    }
                                        
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                    
                    if($profile_pic == "")
                    {
                        if (!empty($profile_pic)) {
                            $pic_path = explode('/', $profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) {
                                $profile_pic = $this->get_user_image_path($profile_pic, $type);
                            } else {
                                $profile_pic = $profile_pic;
                            }
                        } else {
                            $profile_pic = "";
                        }    
                    }

                    $allStatus = $this->getBookingStatus($bookings_date);
                    $get_space_name = Myspace::where('id', $bookings_date->space_id)->first();

                   
                    $service_start_time_user = $this->utcTimeToUserTime($bookings_date->service_start_time, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));
                     
                    $results[] = [
                        'id' => $bookings_date->id,
                        'user_id' => ($user_type == 'cleaner')?$bookings_date->user_id:$bookings_date->service_provider_id,
                        'first_name' => (string) ($user_type == 'cleaner')?$bookings_date->user_first_name:$bookings_date->cleaner_first_name,
                        'last_name' => (string) ($user_type == 'cleaner')?$bookings_date->user_last_name:$bookings_date->cleaner_last_name,
                        'profile_pic' => (string) @$profile_pic,
                        'space_id' => (string) @$bookings_date->space_id,
                        'space_nickname' => (string) @$get_space_name['name'],
                        'booking_address' => (string) @$get_space_name['address'],
                        'latitude' => (string) @$get_space_name['latitude'],
                        'longitude' => (string) @$get_space_name['longitude'],
                        'booking_frequency' => (string) @$bookings_date->booking_frequency,
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) $booking_time,
                        'booking_hours' => (string) @$bookings_date->booking_hours,
                        'is_super_cleaner' => (string) @$bookings_date->cleaner_is_super_cleaner,
                        'ratings' => (string) ($user_type == 'cleaner')?$this->ratingFormat(@$bookings_date->user_rating):$this->ratingFormat(@$bookings_date->cleaner_rating),
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_cancelled' => $bookings_date->is_cancelled,
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete']
                    ];
                }
                $this->success("Booking Found", $results);
            }           
            else
            {
                $this->error("No Booking Found");
            }
            
            
            

            /*
            if ($user_type == 'cleaner') {

                $sql = "SELECT bookings.*, users.first_name, users.last_name, users.profile_pic, users.selfie_image, users.is_super_cleaner, users.is_phone_number_verified 
                FROM bookings LEFT JOIN users ON (bookings.user_id = users.id) WHERE 
                bookings.id = '" . $input['booking_id'] . "'";
                $get_homeowner_details = DB::select($sql);
                if (!empty($get_homeowner_details)) {

                    $user_selfie_image = $get_homeowner_details[0]->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                    
                    if($profile_pic == "")
                    {
                        if (!empty($get_homeowner_details[0]->profile_pic)) {
                            $pic_path = explode('/', $get_homeowner_details[0]->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) {
                                $profile_pic = $this->get_user_image_path($get_homeowner_details[0]->profile_pic, 'homeowners');
                            } else {
                                $profile_pic = $get_homeowner_details[0]->profile_pic;
                            }
                        } else {
                            $profile_pic = "";
                        }    
                    }

                    $allStatus = $this->getBookingStatus($get_homeowner_details[0]);
                    $get_space_name = Myspace::where('id', $get_homeowner_details[0]->space_id)->first();

                    $timezone = $check_user_exists['timezone'];
                    $service_start_time_user = $this->utcTimeToUserTime($get_homeowner_details[0]->service_start_time, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $results = [
                        'id' => $get_homeowner_details[0]->id,
                        'user_id' => $get_homeowner_details[0]->user_id,
                        'first_name' => (string) @$get_homeowner_details[0]->first_name,
                        'last_name' => (string) @$get_homeowner_details[0]->last_name,
                        'profile_pic' => (string) @$profile_pic,
                        'space_id' => (string) @$get_homeowner_details[0]->space_id,
                        'space_nickname' => (string) @$get_space_name['name'],
                        'booking_address' => (string) @$get_space_name['address'],
                        'latitude' => (string) @$get_space_name['latitude'],
                        'longitude' => (string) @$get_space_name['longitude'],
                        'booking_frequency' => (string) @$get_homeowner_details[0]->booking_frequency,
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) @$booking_time,
                        'booking_hours' => (string) @$get_homeowner_details[0]->booking_hours,
                        'is_super_cleaner' => (string) @$get_homeowner_details[0]->is_super_cleaner,
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_cancelled' => $get_homeowner_details[0]->is_cancelled,
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete']

                    ];
                    $this->success("Booking Found", $results);
                } else {
                    $this->error("No Booking Found");
                }
            } else if ($user_type == 'homeOwner') {
                
                $date = date("Y-m-d H:i:s");

                $sql = "SELECT bookings.*, users.first_name, users.last_name, users.rating as rating, users.profile_pic,users.selfie_image, users.is_super_cleaner, users.is_phone_number_verified 
                FROM bookings LEFT JOIN users ON (bookings.service_provider_id = users.id) WHERE bookings.id = " . $input['booking_id'];
                $get_homeowner_details = DB::select($sql);
                if (!empty($get_homeowner_details)) {

                    $allStatus = $this->getBookingStatus($get_homeowner_details[0]);

                    $user_selfie_image = $get_homeowner_details[0]->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

                    if($profile_pic == "")
                    {
                        if (!empty($get_homeowner_details[0]->profile_pic)) {
                            $pic_path = explode('/', $get_homeowner_details[0]->profile_pic);
                            $path_count = count($pic_path);
                            if ($path_count == 1) {
                                $profile_pic = $this->get_user_image_path($get_homeowner_details[0]->profile_pic, 'cleaners');
                            } else {
                                $profile_pic = $get_homeowner_details[0]->profile_pic;
                            }
                        } else {
                            $profile_pic = "";
                        }    
                    }

                    $get_space_name = Myspace::where('id', $get_homeowner_details[0]->space_id)->first();
                    $ratings = Ratings::where('ratings_for', $get_homeowner_details[0]->service_provider_id)->first();

                    $timezone = $check_user_exists['timezone'];
                    $service_start_time_user = $this->utcTimeToUserTime($get_homeowner_details[0]->service_start_time, $timezone);
                    $booking_time = date("H:i:s", strtotime($service_start_time_user));
                    $booking_date_user = date("Y-m-d", strtotime($service_start_time_user));

                    $results = [
                        'id' => $get_homeowner_details[0]->id,
                        'user_id' => $get_homeowner_details[0]->service_provider_id,
                        'first_name' => (string) @$get_homeowner_details[0]->first_name,
                        'last_name' => (string) @$get_homeowner_details[0]->last_name,
                        'profile_pic' => (string) @$profile_pic,
                        'space_id' => (string) @$get_homeowner_details[0]->space_id,
                        'space_nickname' => (string) @$get_space_name['name'],
                        'booking_address' => (string) @$get_space_name['address'],
                        'latitude' => (string) @$get_space_name['latitude'],
                        'longitude' => (string) @$get_space_name['longitude'],
                        'booking_frequency' => (string) @$get_homeowner_details[0]->booking_frequency,
                        'booking_date' => (string) $booking_date_user,
                        'booking_time' => (string) $booking_time,
                        'booking_hours' => (string) @$get_homeowner_details[0]->booking_hours,
                        'is_super_cleaner' => (string) @$get_homeowner_details[0]->is_super_cleaner,
                        'ratings' => (string) $this->ratingFormat(@$get_homeowner_details[0]->rating),
                        'is_upcoming' => $allStatus['is_upcoming'],
                        'is_pending' => $allStatus['is_pending'],
                        'is_on_route' => $allStatus['is_on_route'],
                        'is_completed' => $allStatus['is_completed'],
                        'is_in_progress' => $allStatus['is_in_progress'],
                        'is_cancelled' => $get_homeowner_details[0]->is_cancelled,
                        'pending_start' => $allStatus['pending_start'],
                        'pending_complete' => $allStatus['pending_complete']
                    ];
                    $this->success("Booking Found", $results);
                } else {
                    $this->error("No Booking Found");
                }
            }
            */
        } else {
            $this->error("User Not Exists");
        }
    }

    /* Update User Current Latitude and Longitude */

    public function updateCurrentPositions(Request $request)
    {
        $input = $request->all();
        $this->validation($request->all(),
            [
                'latitude' => 'required',
                'longitude' => 'required',
            ]
        );
        $where = ['id' => $this->userId, 'user_type' => 'cleaner'];
        $check_user_exists = Users::where($where)->first();
        if (!empty($check_user_exists)) {
            $update = ['latitude' => $input['latitude'], 'longitude' => $input['longitude']];
            $update_user_location = DB::table('users')->where('id', $this->userId)->update($update);
            $this->success("User Updated Successfully", "");
        } else {
            $this->error("User Not Exists");
        }
    }

    /* Get User Current Latitude and Longitude */

    public function getCurrentPositions(Request $request)
    {
        $input = $request->all();
        $this->validation($request->all(),
            [
                'cleaner_id' => 'required',
            ]
        );
        $where = ['id' => $this->userId, 'account_blocked' => '0'];
        $check_user_exists = Users::where($where)->first();
        if (!empty($check_user_exists)) {
            $where = ['id' => $input['cleaner_id'], 'user_type' => 'cleaner'];
            $cleaner_details = Users::where($where)->first();
            $latitude = $cleaner_details['latitude'];
            $longitude = $cleaner_details['longitude'];

            $user_selfie_image = $cleaner_details['selfie_image'];
            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

            
            if($profile_pic == "")
            {
                if (!empty($cleaner_details['profile_pic'])) {
                    $pic_path = explode('/', $cleaner_details['profile_pic']);
                    $path_count = count($pic_path);
                    if ($path_count == 1) {
                        $profile_pic = $this->get_user_image_path($cleaner_details['profile_pic'], 'cleaners');
                    } else {
                        $profile_pic = $get_homeowner_details[0]->profile_pic;
                    }
                } else {
                    $profile_pic = "";
                }    
            }
            $user_location = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'profile_pic' => $profile_pic,
            ];

            $this->success("Cleaner Location Found", $user_location);
        } else {
            $this->error("User Not Exists");
        }
    }

    /* Api to get all area of work for cleaners to select */

    public function getAreaOfWork(Request $request)
    {
        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) {
            $all_regions = Regions::all();
            $regions_area = [];
            $regions_val = [];
            if (!empty($all_regions)) {
                foreach ($all_regions as $regions) {
                    $regions_val_decode = json_decode($regions['region_lat_lng']);
                    $regions_val = json_decode(json_encode($regions_val_decode), true);
                    $center_points_decode = json_decode($regions['center_positions']);
                    $center_points = json_decode(json_encode($center_points_decode), true);
                    $regions_area[] = [
                        'id' => $regions['id'],
                        'region_id' => (string) @$regions['region_id'],
                        'region_name' => (string) @$regions['region_name'],
                        'region_latlng' => (!empty($regions_val)) ? $regions_val : [],
                        'center_position' => (!empty($center_points)) ? $center_points : [],
                        'status' => $regions['status'],
                    ];
                }
                $this->success("Regions Found Successfully", $regions_area);
            } else {
                $this->error("No Regions Found");
            }
        } else {
            $this->error("User Not Exists");
        }
    }

    /* Function to get month earnings of cleaner */
    public function getCleanerMonthEarnings(Request $request)
    {

        
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'week_start_from' => 'required',
                'week_end_to' => 'required',
                'week_date' => 'required',
                'filter_type' => 'required',
            ]
        );
        Log::Info("getCleanerMonthEarnings INPUT:",$input);
        $check_user_exists = Users::where('id', $this->userId)->first();

        $bookings = new Bookings();

        if (!empty($check_user_exists)) 
        {
            $timezone = $check_user_exists['timezone'];
            
            if ($input['filter_type'] == 'month') 
            {
                $start_date_user = date('Y-m-01 00:00:00', strtotime($input['week_date']));
                $end_date_user = date('Y-m-t 23:59:59', strtotime($input['week_date']));                
                $start_date = $this->userTimeToUTCTime($start_date_user, $timezone);
                $end_date = $this->userTimeToUTCTime($end_date_user, $timezone);

                $current_sql = "SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '" . $start_date . "' AND 
                `service_start_time` <= '" . $end_date . "' AND 
                `booking_status` = '1' AND 
                `is_completed` = 1 AND 
                `service_provider_id` = '" . $this->userId . "'";

                Log::Info('current_sql: '.$current_sql);
                $current_savings = DB::select($current_sql);
               
                $prices = [];
                $extra_services = [];
                $total_amount = [];
                $services_count = 0;
                if (!empty($current_savings)) 
                {
                    $services_count = count($current_savings);
                    foreach ($current_savings as $savings) 
                    {
                        $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($savings->booking_price);                                
                        $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);                                

                        $prices[] = $amount_pay_cleaner;
                        if (!empty($savings->booking_services)) 
                        {
                            $extraservices = explode(',', $savings->booking_services);
                            foreach ($extraservices as $services) 
                            {
                                $service_amount = Extraservices::where('name', $services)->first();
                                $total_amount[] = $service_amount['price'];
                            }
                        }
                    }
                }
                $extra_services = array_sum($total_amount);
                
                $n = 7;                
                $result = [];

                // calculating upcoming 7 months earnings                
                for ($i = 0; $i < $n; $i++) 
                {
                    $month_start_date = date('Y-m-d 00:00:00', strtotime($start_date_user));
                    $month_end_date = date('Y-m-t 23:59:59', strtotime($month_start_date));

                    $month_start_date = $this->userTimeToUTCTime($month_start_date, $timezone);
                    $month_end_date = $this->userTimeToUTCTime($month_end_date, $timezone);

                    $forcastEarnings = $payablePrice = 0;

                    $forcast_sql = "SELECT (booking_price) as booking_price FROM `bookings` WHERE 
                    `service_start_time` >= '" . $month_start_date . "' AND 
                    `service_start_time` <= '" . $month_end_date . "' AND 
                    `booking_status` = '1' AND 
                    `is_cancelled` = 0 AND 
                    `is_completed` = 0  AND 
                    `is_orphan_booking` = 0 AND  
                    `service_provider_id` = '" . $this->userId . "'";                    
                    $forcast_months_earnings = DB::select($forcast_sql);
                    Log::Info("\n ==> forcast_sql $i: ".$forcast_sql);

                    if (!empty($forcast_months_earnings)) 
                    {
                        foreach ($forcast_months_earnings as $fkey => $forcast_earnings) 
                        {
                            if (0 != $forcast_earnings->booking_price) 
                            {                                
                                $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($forcast_earnings->booking_price);                                
                                $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);

                                $payablePrice += $amount_pay_cleaner;
                                $forcastEarnings = $payablePrice;
                                $prices[] = $amount_pay_cleaner;
                            } 
                    
                        }
                    }

                    $current_months_sql = "SELECT sum(booking_price) as booking_price 
                    FROM `bookings` WHERE 
                    `service_start_time` >= '" . $month_start_date . "' AND 
                    `service_start_time` <= '" . $month_end_date . "' AND 
                    `booking_status` = '1' AND 
                    `is_completed` = 1 AND 
                    `service_provider_id` = '" . $this->userId . "'";
                    $current_months_earnings = DB::select($current_months_sql);

                    Log::Info("\n ==> current_months_earnings: ".$current_months_sql);

                    if (!empty($current_months_earnings[0]->booking_price)) 
                    {
                        foreach ($current_months_earnings as $key => $month_earnings) 
                        {
                            $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($month_earnings->booking_price);       
                            $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);
                            $dates[] = [
                                'date' => date(("M"), strtotime($start_date_user)),
                                'booking_price' => (string)$amount_pay_cleaner,
                                'upcoming_service_amount' => (string)$this->amountToFloat($forcastEarnings),
                            ];
                        }
                    } 
                    else 
                    {
                        $dates[] = [
                            'date' => date(("M"), strtotime($start_date_user)),
                            'booking_price' => '0.00',
                            'upcoming_service_amount' => (string)$this->amountToFloat($forcastEarnings),
                        ];
                    }

                    $start_date_user = date("Y-m-d", strtotime('+1 month', strtotime($start_date_user)));
                }

                $total_earnings = array_sum($prices);

                $current_month = date("Y-m-d 00:00:00");
                $last_day_month = date("Y-m-t 23:59:59", strtotime($current_month));
                 
                $upcoming_services_sql = "SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '" . $start_date . "' AND  
                `service_end_time` <= '" . $end_date . "' AND 
                `booking_status` = '1' AND 
                `is_cancelled` = 0 AND 
                `is_completed` = 0  AND  
                `is_orphan_booking` = 0 AND 
                `service_provider_id` = '" . $this->userId . "'";
                $upcoming_services = DB::select($upcoming_services_sql);
                Log::Info("\n ==> upcoming_services_sql: ".$upcoming_services_sql);
                $total_upcomings = count($upcoming_services);                
                if (!empty($upcoming_services)) 
                {
                    foreach ($upcoming_services as $services) 
                    {
                        $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($services->booking_price);       
                        $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);

                        $upcoming_services_values[] = $amount_pay_cleaner;
                    }
                    $total_upcoming_values = array_sum($upcoming_services_values);
                } 
                else 
                {
                    $total_upcoming_values = 0;
                }
                
                $reject_services = DB::select("SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '" . $start_date . "' AND 
                `service_start_time` <= '" . $end_date . "' AND                 
                `service_provider_id` = '" . $this->userId . "' AND 
                `is_cancelled` = 1"
                );
                $reject_service = count($reject_services);
                               
                Log::Info("DATE LOG:: ".json_encode($dates));

                $response['time'] = date("F", strtotime($input['week_date'])) . ' Earnings';
                $response['total_amount'] = (string) $this->amountToFloat($total_earnings);
                $response['stats'] = $dates;
                $response['services_completed'] = $services_count;
                $response['upcoming_services'] = $total_upcomings;
                $response['upcoming_service_amount'] = (string) $this->amountToFloat($total_upcoming_values);
                $service = round($extra_services, 2);
                $response['extra_services'] = (string) @$service;
                $response['rejected_services'] = $reject_service;
                $response['service_fees'] = $this->charge_deduct_cleaner_total_percent;

                $this->success("Services Earnings", $response);

            } 
            else if ($input['filter_type'] == 'week') 
            {
                $week_start_date = date("Y-m-d 00:00:00", strtotime($input['week_start_from']));
                $week_end_date = date("Y-m-d 23:59:59", strtotime($input['week_end_to']));
                $week_start_date = $this->userTimeToUTCTime($week_start_date, $timezone);
                $week_end_date = $this->userTimeToUTCTime($week_end_date, $timezone);

                Log::Info("week_start_date: ".$week_start_date);
                Log::Info("week_end_date: ".$week_end_date."\n\n");
                    
                $current_sql = "SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '" . $week_start_date . "' AND 
                `service_start_time` <= '" . $week_end_date . "' AND 
                `booking_status` = '1' AND 
                `is_completed` = 1 AND 
                `service_provider_id` = '" . $this->userId . "'";

                
                Log::Info('current_sql: '.$current_sql);
                $current_savings = DB::select($current_sql);

                $prices = [];
                $extra_services = [];
                $total_amount = [];
                $services_count = 0;
                
                if (!empty($current_savings)) 
                {
                    $services_count = count($current_savings);
                    foreach ($current_savings as $savings) 
                    {
                        $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($savings->booking_price);       
                        $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);
                        $prices[] = $amount_pay_cleaner;

                        if (!empty($savings->booking_services)) 
                        {
                            $extraservices = explode(',', $savings->booking_services);
                            foreach ($extraservices as $services) 
                            {
                                $service_amount = Extraservices::where('name', $services)->first();
                                $total_amount[] = $service_amount['price'];
                            }
                        }
                    }
                }

                Log::Info("prices",$prices);
                Log::Info("total_amount",$total_amount);
                $extra_services = array_sum($total_amount);
                
                $n = 7;
                $start_date = date("Y-m-d 00:00:00", strtotime($input['week_start_from']));
                
                $result = [];
                for ($i = 0; $i < $n; $i++) 
                {
                    $weekly_start_date = date('Y-m-d 00:00:00', strtotime($start_date));
                    $weekly_end_date = date('Y-m-d 23:59:59', strtotime($start_date));

                    $weekly_start_date = $this->userTimeToUTCTime($weekly_start_date, $timezone);
                    $weekly_end_date = $this->userTimeToUTCTime($weekly_end_date, $timezone);

                    $forcastEarnings = 0;
                    $payablePrice = 0;
                    $forcast_sql = "SELECT (booking_price) as booking_price FROM `bookings` WHERE 
                        `service_start_time` >= '" . $weekly_start_date . "' AND 
                        `service_start_time` <= '" . $weekly_end_date . "' AND 
                        `booking_status` = '1' AND 
                        `is_cancelled` = 0 AND 
                        `is_completed` = 0  AND 
                        `is_orphan_booking` = 0  AND 
                        `service_provider_id` = '" . $this->userId . "'";
                                                                
                    $forcast_months_earnings = DB::select($forcast_sql);
                    if (!empty($forcast_months_earnings)) 
                    {
                        foreach ($forcast_months_earnings as $fkey => $forcast_earnings) 
                        {
                            if (0 != $forcast_earnings->booking_price) 
                            {                                                                
                                $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($forcast_earnings->booking_price);       
                                $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);

                                $payablePrice += $amount_pay_cleaner;
                                $forcastEarnings = $payablePrice;
                                $prices[] = $amount_pay_cleaner;
                            } 
                            
                        }
                    }                    

                    $current_week_sql = "SELECT * FROM `bookings` WHERE 
                    `service_start_time` >= '" . $weekly_start_date . "' AND 
                    `service_start_time` <= '" . $weekly_end_date . "' AND 
                    `booking_status` = '1' AND 
                    `is_completed` = 1 AND 
                    `service_provider_id` = '" . $this->userId . "'";                    
                                        
                    $current_week_earnings = DB::select($current_week_sql);

                    $earning_count = count($current_week_earnings);

                    Log::Info("LOOP $i : weekly_start_date: ". $weekly_start_date);
                    Log::Info("LOOP $i : weekly_end_date: ". $weekly_end_date);                            
                    Log::Info("\n\n forcast_sql: ".$forcast_sql);
                    Log::Info("\n\n current_week_earnings: ".$current_week_sql);                    
                    Log::Info("forcastEarnings ". $forcastEarnings);
                    Log::Info("forcastEarnings:" .$forcastEarnings.'=='.$earning_count.'=='.$start_date);

                    if ($earning_count > 0) 
                    {
                        foreach ($current_week_earnings as $key => $month_earnings) 
                        {
                            $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($month_earnings->booking_price);       
                            $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);
                           
                            $dates[] = [
                                'date' => date("Y-m-d", strtotime($start_date)),
                                'booking_price' => (string) $amount_pay_cleaner,
                                'upcoming_service_amount' => (string) $this->amountToFloat($forcastEarnings),
                            ];

                        }
                    } 
                    else 
                    {
                        
                        $dates[] = [
                            'date' => date(("Y-m-d"), strtotime($start_date)),
                            'booking_price' => '0.00',
                            'upcoming_service_amount' => (string) $this->amountToFloat($forcastEarnings),
                        ];
                    }

                    $start_date = date("Y-m-d H:i:s", strtotime('+1 day', strtotime($start_date)));
                }

                $total_earnings = array_sum($prices);
            
                array_multisort($dates, SORT_ASC);

                Log::Info("DATE LOG 2: ",$dates);

                Log::Info("OUT SIDE LOOP : week_start_date: ". $week_start_date);
                Log::Info("OUT SIDE LOOP: week_end_date: ". $week_end_date."\n\n");

                $reject_service_sql = "SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '" . $week_start_date . "' AND 
                `service_start_time` <= '" . $week_end_date . "' AND                
                `service_provider_id` = '" . $this->userId . "' AND  
                `is_cancelled` = 1 ";

                Log::Info(" reject_service_sql: ". $reject_service_sql);

                $reject_services = DB::select($reject_service_sql);        
                $reject_service = count($reject_services);

                $upcoming_services_sql = "SELECT * FROM `bookings` WHERE 
                `service_start_time` >= '" . $week_start_date . "' AND 
                `service_start_time` <= '" . $week_end_date . "' AND 
                `booking_status` = '1' AND 
                `is_cancelled` = 0 AND 
                `is_completed` = 0  AND 
                `is_orphan_booking` = 0  AND 
                `service_provider_id` = '" . $this->userId . "'";

                Log::Info("upcoming_services_sql : ".$upcoming_services_sql."\n\n");

                $upcoming_services = DB::select($upcoming_services_sql);
                $total_upcomings = count($upcoming_services);                                

                if (!empty($upcoming_services)) 
                {
                    foreach ($upcoming_services as $services) 
                    {
                        $payPriceWithTaxes = $bookings->getFinalPriceForCleaner($services->booking_price);       
                        $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);

                        $upcoming_services_values[] = $amount_pay_cleaner;
                    }
                    $total_upcoming_values = array_sum($upcoming_services_values);
                } 
                else 
                {
                    $total_upcoming_values = 0;
                }
                
                $response['time'] = date("F-d", strtotime($input['week_start_from'])) . ' - ' . date("F-d", strtotime($input['week_end_to'])) . ' Earnings';
                $response['total_amount'] = (string) $this->amountToFloat($total_earnings);
                $response['stats'] = $dates;
                $response['services_completed'] = $services_count;
                $response['upcoming_services'] = $total_upcomings;
                $response['upcoming_service_amount'] = (string) $this->amountToFloat($total_upcoming_values);
                $service = round($extra_services, 2);
                $response['extra_services'] = (string) @$service;
                $response['rejected_services'] = $reject_service;
                $response['service_fees'] = $this->charge_deduct_cleaner_total_percent;

                $this->success("Services Earnings", $response);
            }
        }
    }

    /* Function to create Daily, Weekly and Monthly type of bookings */

    public function createFrequencyBooking(Request $request)
    {
        $cleaners = DB::select("SELECT * FROM `users` WHERE `user_type` = 'cleaner' AND `account_blocked` = '0' AND `status` = '1'");
        if (!empty($cleaners)) {
            foreach ($cleaners as $cleaner) {
                $cleaner_bookings = DB::select("SELECT * FROM `bookings` WHERE (`booking_frequency` = 'Daily' OR `booking_frequency` = 'Weekly' OR `booking_frequency` = 'Monthly') AND `service_provider_id` = '" . $cleaner->id . "' AND `booking_status` = '1' ORDER BY `id` DESC");
                foreach ($cleaner_bookings as $bookings) {
                    if ($bookings->booking_frequency == 'Weekly') {
                        $date = date("Y-m-d");

                        $service_start_time = date("Y-m-d H:i:s", strtotime("+7 days", strtotime($bookings->service_start_time)));
                        $service_end_time = date("Y-m-d H:i:s", strtotime("+7 days", strtotime($bookings->service_end_time)));
                        $user_booking_date = date('Y-m-d', strtotime($service_start_time));

                        if ($user_booking_date == $date) {
                            $job_id = $this->generateUniqueJobID();

                            $booking_array = [
                                'user_id' => $bookings->user_id,
                                'service_provider_id' => $bookings->service_provider_id,
                                'space_id' => $bookings->space_id,
                                'booking_services' => $bookings->booking_services,
                                'booking_date' => $user_booking_date,
                                'service_start_time' => $service_start_time,
                                'service_end_time' => $service_end_time,
                                'booking_hours' => $bookings->booking_hours,
                                'booking_frequency' => $bookings->booking_frequency,
                                'booking_address' => $bookings->booking_address,
                                'latitude' => $bookings->latitude,
                                'longitude' => $bookings->longitude,
                                'special_instructions' => $bookings->special_instructions,
                                'booking_status' => '1',
                                'is_completed' => '1',
                                'is_cancelled' => '0',
                                'accept_at' => date("Y-m-d H:i:s"),
                                'job_id' => $job_id,
                            ];
                            $create_bookings = Bookings::create($booking_array);
                            if (!empty($create_bookings)) {
                                $this->success("Bookings create successfully");
                            } else {
                                $this->error("Something Went Wrong");
                            }
                        }
                    } else if ($bookings->booking_frequency == 'Daily') {
                        $date = date("Y-m-d");
                        $user_booking_date = date("Y-m-d", strtotime("+1 day", strtotime($bookings->service_start_time)));
                        if ($user_booking_date == $date) {

                            $job_id = $this->generateUniqueJobID();

                            $service_start_time = date("Y-m-d H:i:s", strtotime("+1 day", strtotime($bookings->service_start_time)));
                            $service_end_time = date("Y-m-d H:i:s", strtotime("+1 day", strtotime($bookings->service_end_time)));

                            $booking_array = [
                                'user_id' => $bookings->user_id,
                                'service_provider_id' => $bookings->service_provider_id,
                                'space_id' => $bookings->space_id,
                                'booking_services' => $bookings->booking_services,
                                'booking_date' => $user_booking_date,
                                'service_start_time' => $service_start_time,
                                'service_end_time' => $service_end_time,
                                'booking_hours' => $bookings->booking_hours,
                                'booking_frequency' => $bookings->booking_frequency,
                                'booking_address' => $bookings->booking_address,
                                'latitude' => $bookings->latitude,
                                'longitude' => $bookings->longitude,
                                'special_instructions' => $bookings->special_instructions,
                                'booking_status' => '1',
                                'is_completed' => '1',
                                'is_cancelled' => '0',
                                'accept_at' => date("Y-m-d H:i:s"),
                                'job_id' => $job_id,
                            ];
                            $create_bookings = Bookings::create($booking_array);
                            if (!empty($create_bookings)) {
                                $this->success("Bookings create successfully");
                            } else {
                                $this->error("Something Went Wrong");
                            }
                        }
                    } else if ($bookings->booking_frequency == 'Monthly') {
                        $date = date("Y-m-d");
                        $user_booking_date = date("Y-m-d", strtotime("+1 month", strtotime($bookings->service_start_time)));
                        if ($user_booking_date == $date) {

                            $job_id = $this->generateUniqueJobID();

                            $service_start_time = date("Y-m-d H:i:s", strtotime("+1 month", strtotime($bookings->service_start_time)));
                            $service_end_time = date("Y-m-d H:i:s", strtotime("+1 month", strtotime($bookings->service_end_time)));

                            $booking_array = ['user_id' => $bookings->user_id,
                                'service_provider_id' => $bookings->service_provider_id,
                                'space_id' => $bookings->space_id,
                                'booking_services' => $bookings->booking_services,
                                'booking_date' => $user_booking_date,
                                'service_start_time' => $service_start_time,
                                'service_end_time' => $service_end_time,
                                'booking_hours' => $bookings->booking_hours,
                                'booking_price' => $bookings->booking_price,
                                'booking_type' => $bookings->booking_type,
                                'booking_frequency' => $bookings->booking_frequency,
                                'booking_address' => $bookings->booking_address,
                                'latitude' => $bookings->latitude,
                                'longitude' => $bookings->longitude,
                                'special_instructions' => $bookings->special_instructions,
                                'booking_status' => '1',
                                'is_completed' => '1',
                                'is_cancelled' => '0',
                                'accept_at' => date("Y-m-d H:i:s"),
                                'job_id' => $job_id,
                            ];
                            $create_bookings = Bookings::create($booking_array);
                            if (!empty($create_bookings)) {
                                $this->success("Bookings create successfully");
                            } else {
                                $this->error("Something Went Wrong");
                            }
                        }
                    } else if ($bookings->booking_frequency == 'Bi-Weekly') {
                        $date = date("Y-m-d");
                        $user_booking_date = date("Y-m-d", strtotime("+16 days", strtotime($bookings->service_start_time)));
                        if ($user_booking_date == $date) {

                            $job_id = $this->generateUniqueJobID();

                            $service_start_time = date("Y-m-d H:i:s", strtotime("+16 days", strtotime($bookings->service_start_time)));
                            $service_end_time = date("Y-m-d H:i:s", strtotime("+16 days", strtotime($bookings->service_end_time)));

                            $booking_array = ['user_id' => $bookings->user_id,
                                'service_provider_id' => $bookings->service_provider_id,
                                'space_id' => $bookings->space_id,
                                'booking_services' => $bookings->booking_services,
                                'booking_date' => $user_booking_date,
                                'service_start_time' => $service_start_time,
                                'service_end_time' => $service_end_time,
                                'booking_hours' => $bookings->booking_hours,
                                'booking_price' => $bookings->booking_price,
                                'booking_type' => $bookings->booking_type,
                                'booking_frequency' => $bookings->booking_frequency,
                                'booking_address' => $bookings->booking_address,
                                'latitude' => $bookings->latitude,
                                'longitude' => $bookings->longitude,
                                'special_instructions' => $bookings->special_instructions,
                                'booking_status' => '1',
                                'is_completed' => '1',
                                'is_cancelled' => '0',
                                'accept_at' => date("Y-m-d H:i:s"),
                                'job_id' => $job_id,
                            ];
                            $create_bookings = Bookings::create($booking_array);
                            if (!empty($create_bookings)) {
                                $this->success("Bookings create successfully");
                            } else {
                                $this->error("Something Went Wrong");
                            }
                        }
                    }
                }
            }
        }
    }

    /* Booking Marked As Complete     
    */

    public function markAsComplete(Request $request)
    {

        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
                'home_owner_id' => 'required',
            ]
        );
        $bookings = new Bookings();
        
        Log::Info("markAsComplete param: " . json_encode($input));

        $where = ['id' => $input['booking_id']];
        $check_booking_exists = Bookings::where($where)->first();

        if(!empty($check_booking_exists))
        {
            $current_time = date('Y-m-d H:i:s');
           
            if($check_booking_exists['is_in_progress']  != 1)
            {
                $this->error("Sorry, you can't complete the job as its not started yet.");
            }
            if($check_booking_exists['service_end_time'] > $current_time)
            {
                $remainingMinutes = (strtotime($check_booking_exists['service_end_time']) - strtotime($current_time)) / 60;

                if($remainingMinutes > 30)
                {
                    $this->error("Sorry, you can't complete the job before the job end time.");
                }
            }

        }
        else
        {
            $this->error("Sorry, booking does not exist.");
        }

        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) {
            
            $homeOwnerCharges = $check_booking_exists['amount_paid'];
            $homeOwnerChargesInCent = $homeOwnerCharges*100;

            $chargeId = $check_booking_exists['charge_id'];
            if(!empty($chargeId))
            {
                
                $owner_details = Users::where('id', $check_booking_exists['user_id'])->first();
                $stripeUserDetails = StripeUserDetails::where(['user_id' => $check_booking_exists['user_id']])->first();                                
                $cusId = $stripeUserDetails->customer_id;        

                $charge_params = array();
                $charge_params['charge_id'] = $chargeId;
                $charge_params['total_in_cent'] = $homeOwnerChargesInCent;
                $charge_params['cusId'] = $cusId;
                $charge_params['first_name'] = $owner_details['first_name'] ;
                $charge_params['email'] = $owner_details['email'] ;
                $charge_params['job_id'] = $check_booking_exists['job_id'];                
                $charge_params['booking_id'] = $check_booking_exists['id'];                
                $charge = $this->captureStripeCharge($charge_params);                                
                
                if (!empty($charge)) {
                    // updating booking table with charge id and transaction id
                    $stripeData = $this->saveStripeCharges($charge, $input['booking_id']);
                    
                    if ($stripeData) {
                        
                        
                        $transfer = $bookings->transferToCleaner($input['booking_id']);
                        if($transfer['success'] == false)
                        {
                            $this->error($transfer['message']);
                        }
                        if($transfer['success'] == true)
                        {
                            $this->applyCleanerReferralAmount($this->userId);                                        
                
                            /* Send Notification to homeowner that cleaner complete booking and give him review and rating */
                            
                            $home_owner_id = $check_booking_exists['user_id'];                    
                            $owner_details = Users::where('id', $home_owner_id)->first();
                            $cleaner_name = $check_user_exists['first_name'].' '.$check_user_exists['last_name'];
                
                            $payload['title'] = "Booking Completed";
                            $payload['body'] = "Please rate your recent booking with ".$cleaner_name;
                            $payload['type'] = 'booking completed';
                            $payload['user_type'] = 'homeOwner';
                
                            
                            $today = date('Y-m-d');
                            $service_end_date = date('Y-m-d',strtotime($check_booking_exists['service_end_time']));
                            if($service_end_date == $today && $owner_details['push_notification'] == 1)
                            {
                                $this->send_booking_complete_notification($owner_details['device_token'], $payload);
                            }
                            
                            $notification_array = ['sender_id' => $this->userId, 'receiver_id' => $home_owner_id, 'booking_id' => $input['booking_id'], 'status' => '0','notification_read'=>'0'];
                            $notifications = Notifications::create($notification_array);
                
                            $array = [
                                'id' => $input['booking_id'],
                                'user_id' => $input['home_owner_id'],
                                'service_provider_id' => $this->userId,
                            ];
                            $update_array = ['is_completed' => 1,'completed_at' => date('Y-m-d H:i:s'), 'is_in_progress' => 0, 'is_on_route' => 0];
                            $update_value = DB::table('bookings')->where($array)->update($update_array);

                            $this->success("Booking Marked as complete", ['charges' => $stripeData]);
                        }
                        else
                        {
                            $this->error("Transfer to cleaner failed.");    
                        }
                        
                    } else {
                        $this->error("Stripe data not saved in db.");
                    }
                } else {
                    $this->error("Some issue occured with stripe. Not able to deduct at this time.");
                }
            }
            else {
                
                $transfer = $bookings->transferToCleaner($input['booking_id']);
                if($transfer['success'] == false)
                {
                    $this->error($transfer['message']);
                }
                if($transfer['success'] == true)
                {
                    $this->applyCleanerReferralAmount($this->userId);                                        
        
                    /* Send Notification to homeowner that cleaner complete booking and give him review and rating */
                    
                    $home_owner_id = $check_booking_exists['user_id'];                    
                    $owner_details = Users::where('id', $home_owner_id)->first();
                    $cleaner_name = $check_user_exists['first_name'].' '.$check_user_exists['last_name'];
        
                    $payload['title'] = "Booking Completed";
                    $payload['body'] = "Please rate your recent booking with ".$cleaner_name;
                    $payload['type'] = 'booking completed';
                    $payload['user_type'] = 'homeOwner';
        
                    
                    if ($owner_details['push_notification'] == 1) {
                        $this->send_booking_complete_notification($owner_details['device_token'], $payload);
                    }
                    $notification_array = ['sender_id' => $this->userId, 'receiver_id' => $home_owner_id, 'booking_id' => $input['booking_id'], 'status' => '0','notification_read'=>'0'];
                    $notifications = Notifications::create($notification_array);
        
                    $array = [
                        'id' => $input['booking_id'],
                        'user_id' => $input['home_owner_id'],
                        'service_provider_id' => $this->userId,
                    ];
                    $update_array = ['is_completed' => 1,'completed_at' => date('Y-m-d H:i:s'), 'is_in_progress' => 0, 'is_on_route' => 0];
                    $update_value = DB::table('bookings')->where($array)->update($update_array);

                    $this->success("Booking Marked as complete", ['charges' => $stripeData]);
                }
                else
                {
                    $this->error("Transfer to cleaner failed.");    
                }
            }
           
        } else {
            $this->error("User not exists");
        }
    }

    
    public function markAsStart(Request $request)
    {

        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
                'owner_id' => 'required',
            ]
        );

        $owner_id = $input['owner_id'];

        Log::Info("markAsStart input:" . json_encode($input));

        $check_user_exists = Users::where('id', $owner_id)->first();
        if (!empty($check_user_exists)) {
            $booking_id = $input['booking_id'];
            $booking = Bookings::where('id', $booking_id)->first();
            $start_time = strtotime($booking->service_start_time);
            $current_time = strtotime(date("Y-m-d H:i:s"));

            if ($current_time >= $start_time) {
                
                $cleaner_detail = Users::where('id', $booking->service_provider_id)->first();
                $cleaner_name = $cleaner_detail['first_name'].' '.$cleaner_detail['last_name'];
                                
                $booking->is_completed = 0;
                $booking->is_in_progress = 1;
                $booking->is_on_route = 0;
                $booking->save();                                
                
                $payload['title'] = "Booking In Progress";
                $payload['body'] = $cleaner_name. " start working";
                $payload['type'] = 'booking inprogress';
                $payload['user_type'] = 'homeOwner';
                
                if ($check_user_exists['push_notification'] == 1) {
                    $this->send_booking_in_progress_notification($check_user_exists['device_token'], $payload);
                }
                
                $status = $this->getBookingStatus($booking);
                $data = ["booking" => $booking, 'booking_id' => $input['booking_id'], 'booking_status' => $status];

                $this->success("Your Booking Has Been Started!", $data);
            } else {
                $this->error("Sorry, you can’t start the job before the start time.", ['err' => 1]);
            }
        } else {
            $this->error("User not exists", ['err' => 0]);
        }
    }

    /* Read notification and change status of notification */

    public function notificationRead(Request $request)
    {

        $check_user_exists = Users::where('id', $this->userId)->first();
        if (!empty($check_user_exists)) {
            $update_status = Notifications::where('receiver_id', $this->userId)->update(['notification_read' => '1']);
            $this->success("Notifications Read Successfully",
                ['service_fees' => $this->skep_percent]);
        } else {
            $this->error("User Not Exists", "");
        }
    }

    public function get_image_path($image = '', $folder_name = '')
    {
        if (!empty($image)) {
            $image_path = url('/') . '/public/images/extra_services/' . $image;
        } else {
            $image_path = '';
        }
        return $image_path;
    }

    public function get_user_image_path($image = '', $folder_name = '')
    {
        if (!empty($image)) {
            $image_path = url('/') . '/public/images/users/' . $folder_name . '/' . $image;
        } else {
            $image_path = '';
        }
        return $image_path;
    }
    
    /**
     * Calculate the number of hrs for bedrooms based on quantity
     */
    private function getBedroomsServiceHrs($quantity)
    {
        switch ($quantity) {
            case 0:
                $hrs = 1.5;
                break;
            case 1:
                $hrs = 1.5;
                break;
            case 2:
                $hrs = 2;
                break;
            case 3:
                $hrs = 2.5;
                break;
            case 4:
                $hrs = 3;
                break;
            case 5:
                $hrs = 3;
                break;
            default:
                $hrs = 0;
        }
        return $hrs;
    }
    /**
     * Calculate the number of hrs for bathrooms based on quantity
     */
    private function getBathroomsServiceHrs($quantity)
    {
        switch ($quantity) {
            case 1:
                $hrs = 1;
                break;
            case 2:
                $hrs = 1.5;
                break;
            case 3:
                $hrs = 2;
                break;
            case 4:
                $hrs = 2.5;
                break;
            default:
                $hrs = 0;
        }
        return $hrs;
    }
    /**
     * Calculate the number of hrs for bedrooms based on quantity
     */
    private function getDenServiceHrs($quantity)
    {
        switch ($quantity) {
            case 0:
                $hrs = 0;
                break;
            case 1:
                $hrs = 0.5;
                break;
            default:
                $hrs = 0;
        }
        return $hrs;
    }
    /**
     * Calculate the number of hrs for bedrooms based on quantity
     */
    private function getFamilyRoomServiceHrs($quantity)
    {
        switch ($quantity) {
            case 0:
                $hrs = 0;
                break;
            case 1:
                $hrs = 0.5;
                break;
            default:
                $hrs = 0;
        }
        return $hrs;
    }
    /**
     * Calculate the number of hrs for bedrooms based on quantity
     */
    private function getDiningRoomServiceHrs($quantity)
    {
        switch ($quantity) {
            case 0:
                $hrs = 0;
                break;
            case 1:
                $hrs = 0.25;
                break;
            default:
                $hrs = 0;
        }
        return $hrs;
    }
    /**
     * Calculate the number of hrs for bedrooms based on quantity
     */
    private function getPowderRoomServiceHrs($quantity)
    {
        switch ($quantity) {
            case 0:
                $hrs = 0;
                break;
            case 1:
                $hrs = 0.25;
                break;
            default:
                $hrs = 0;
        }
        return $hrs;
    }
    
    
    private function getDateDiff($date1, $date2)
    {
        $diff = abs(strtotime($date2) - strtotime($date1));
        $years = floor($diff / (365 * 60 * 60 * 24));
        $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
        $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
        $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));
        $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);
        $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));
        printf("%d years, %d months, %d days, %d hours, %d minuts\n, %d seconds\n", $years, $months, $days, $hours, $minuts, $seconds);die;
        $diff = "";
        return $diff;
    }
    /*
     * Function used to deduct and refund money for home owner in case
     * home owner cancel instant booking within 5 mins and advanced booking within 24hrs
     */
    public function deductHomeOwnerForCancellation(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
            ]
        );
        $userId = $this->userId;
        $booking_id = $input['booking_id'];

        /* Check user that cancel booking exist or not */
        $check_user_exists = Users::where('id', $userId)->first();
        if (!empty($check_user_exists)) 
        {
            $user_type = $check_user_exists['user_type'];
            /* Check the user cancel booking is of homeOwner type */
            if ($user_type == 'homeOwner') 
            {
                // Checking customer id correspond to home owner
                $stripeUserDetails = StripeUserDetails::where(['user_id' => $userId])->first();
                if ($stripeUserDetails) 
                {
                    $cusId = $stripeUserDetails->customer_id;
                } 
                else 
                {
                    $this->error("Not found customer id correspond to this user.", ['err' => 2]);
                }
                /* Getting booking details */
                
                $check_booking = Bookings::where('id', $booking_id)->first();
                $bookPrice = $refundPrice = 0;
                if ($check_booking) 
                {
                    
                    $check_booking['cancelled_by'] = 'homeOwner';                
                    $check_booking['homeowner_penalty_percent'] = $this->homeowner_penalty_percent;
                    $stripeData = $this->refundHomeOwnerForCancellation($userId,$check_booking);

                    // this is for to delete all clear notification
                    $this->deleteNotification($booking_id);

                    $msg = "Booking is cancelled with ".$this->homeowner_penalty_percent."% penalty and the remaining amount is refunded successfully.";
                    
                    if(!empty($check_booking['service_provider_id']))
                    {
                        $get_cleaner_details = Users::where('id', $check_booking['service_provider_id'])->first();
                        $payload['title'] = 'Booking Cancelled';
                        $payload['body'] = 'Your homeowner has cancelled the booking request.';
                        $device_token = $get_cleaner_details['device_token'];
                        if ($get_cleaner_details['push_notification'] == 1) 
                        {
                            $this->send_cancel_notification($device_token, $payload);
                        }
                    }
                    
                    
                    $this->success($msg, ['refund' => $stripeData]);
                                        
                } 
                else 
                {
                    $this->error("Booking does not exist with this id.", ['err' => 3]);
                }                                
            } 
            else 
            {
                $this->error("Wrong user type. Its applicable only for Home Owners", ['err' => 1]);
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
    private function saveStripeCharges($charge, $booking_id)
    {
        $stripeData = [];
        $stripeData = ['transaction_id' => $charge['balance_transaction']];
        // updating booking table with charge id and transaction id
        $updateStripeData = Bookings::SaveChargesStripeData($stripeData, $booking_id);
        if ($updateStripeData) {
            $chargeId = $charge['id'];
            $stripeData['customer'] = $charge['customer'];
            $stripeData['description'] = $charge['description'];
            $stripeData['payment_method'] = $charge['payment_method'];
        }
        Log::Info("update Stripe charge data : " . json_encode($stripeData));
        return $stripeData;
    }
   
/*
 * Function used to reduce cleaner rating if cancellation time is less than given tm
 */
    public function deductRatingCleaners(Request $request)
    {
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'booking_id' => 'required',
            ]
        );
        $booking_id = $input['booking_id'];
/* If cleaner cancel the booking then his rating reduct by 0.2 points */
        $where = ['id' => $booking_id, 'service_provider_id' => $this->userId];
        $check_booking_exists = Bookings::where($where)->first();
        if (!empty($check_booking_exists)) {
            $user = Users::where('id', $this->userId)->first();
            if (!empty($user)) {
                $reduct_value = $user['rating'] - 0.3;
                if ($reduct_value <= 0) {
                    $reduct_value = 0;
                }
                $reduct_value = $this->ratingFormat($reduct_value);
                $update_data = Users::where(['id' => $this->userId])->update(['rating' => $reduct_value]);
                $owner_details = Users::where('id', $check_booking_exists['user_id'])->first();
                
                $home_owner_id = $check_booking_exists['user_id'];                    
                $service_provider_id = $check_booking_exists['service_provider_id'];                    
                
                $check_booking_exists['cancelled_by'] = 'cleaner';                
                $check_booking_exists['homeowner_penalty_percent'] = 0;
                $this->refundHomeOwnerForCancellation($home_owner_id,$check_booking_exists);                
                
                // this is for to delete all clear notification
                $this->deleteNotification($booking_id);

                
                $payload['title'] = 'Booking Cancelled';
                $payload['body'] = 'Your booking is cancelled. Please create a new booking with another cleaner';
                if ($owner_details['push_notification'] == 1) {
                    $notify_user = $this->send_cancel_notification($owner_details['device_token'], $payload);
                }
                $this->success("Booking is cancelled with 0.3 penalty.", ['ratings' => $reduct_value]);
            } else {
                $this->error("User does not exist.", ['err' => 1]);
            }
        } else {
            $this->error("Booking does not exist.", ['err' => 0]);
        }

    }
    function getBookingStatus($value)
    {
        $start_time = strtotime(date("Y-m-d H:i:s"));
        $end_time = strtotime($value->service_start_time);
        $service_end_time = strtotime($value->service_end_time);

        $difference = $end_time - $start_time;
        $time_diff = floor($difference / 60);

        $is_pending = '0';
        $is_in_progress = $value->is_in_progress;
        $is_on_route = $value->is_on_route;
        $is_completed = $value->is_completed;

        $is_upcoming = '0';

        $is_cancelled = $value->is_cancelled;

        $pending_start = '0';
        $pending_complete = '0';

        $STEP = 1;

        if($value->booking_status == '0')
        {
            $is_pending = '1';
        }
        
        Log::Info("Booking ID: " . $value->id);
        if ($time_diff > 0 && $time_diff <= 45 && $value->booking_status == '1' && $is_on_route == 0 && $value->is_completed == 0) 
        {
            $STEP = 2;
            $is_on_route = '1';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => 1]);
            //Update status to DB
            $is_pending = '0';
            $is_completed = $value->is_completed;
            $is_upcoming = '0';
        } 
        else if ($time_diff > 0 && $time_diff <= 45 && $value->booking_status == 0) 
        {

            $STEP = 3;
            $is_on_route = '0';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => $is_on_route]);
            $is_pending = '1';
            $is_completed = $value->is_completed;
            $is_upcoming = '0';
        } 
        else if ($time_diff > 45 && $value->booking_status == '1') 
        {

            $STEP = 4;
            $is_on_route = '0';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => $is_on_route]);
            $is_pending = '0';
            $is_completed = $value->is_completed;
            $is_upcoming = '1';
        } 
        else if ($time_diff > 45 && $value->booking_status == 0) 
        {

            $STEP = 5;
            $is_on_route = '0';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => $is_on_route]);
            $is_pending = '1';
            $is_completed = $value->is_completed;
            $is_upcoming = '0';
        } 
        else if ($value->is_in_progress == 1) 
        {

            $STEP = 6;
            $is_on_route = '0';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => $is_on_route]);
            $is_pending = '0';
            $is_in_progress = 1;
            $is_completed = $value->is_completed;
            $is_upcoming = '0';
        } 
        else if ($value->is_completed == 1) 
        {

            $STEP = 7;
            $is_on_route = '0';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => $is_on_route]);
            $is_pending = '0';
            $is_in_progress = 0;
            $is_completed = $value->is_completed;
            $is_upcoming = '0';
        } 
        else if ($value->is_cancelled == 1) 
        {

            $STEP = 8;
            $is_on_route = '0';
            $is_pending = '0';
            $is_in_progress = 0;
            $is_completed = 0;
            $is_upcoming = '0';

        }

        if ($end_time > $start_time && $time_diff > 45) 
        {

            $STEP = 9;
            $is_on_route = '0';
            $update_value = Bookings::where(['id' => $value->id])->update(['is_on_route' => $is_on_route]);
        }
        Log::Info("STEP:=======================");
        Log::Info("STEP: " . $STEP);
        Log::Info("STEP:=======================");
        Log::info('TIME DIFF: ' . $value->id . ' | time_diff: ' . $time_diff . '====' . $value->booking_status . '==now: ' . date("Y-m-d H:i:s") . '=service time: ' . $value->service_start_time);

        if($value->booking_status == 1 && $is_cancelled != 1  && $is_completed != 1 && $is_in_progress != 1)
        {
            if($start_time >  $end_time )
            {
                $pending_start  = '1';
                $is_on_route = '0';
            }
        }

        if($value->booking_status == 1 && $is_cancelled != 1 && $is_completed != 1 && $is_in_progress == 1)
        {
            if($start_time >  $service_end_time)
            {
                $pending_complete  = '1';
                $is_on_route = '0';
                $is_in_progress = '0';
            }
        }

        $booking_status =  [
            'is_on_route' => (string) $is_on_route, 
            'is_pending' => (string) $is_pending, 
            'is_completed' => (string) $is_completed, 
            'is_upcoming' => (string) $is_upcoming, 
            'is_in_progress' => (string) $is_in_progress,
            'is_cancelled' => (string) $is_cancelled,
            'pending_start' => (string) $pending_start,
            'pending_complete' => (string) $pending_complete
        ];

        Log::info('BOOKING STATUS: ',$booking_status);
        return $booking_status;
    }    

    public function getBookingStatusInAdmin($value)
    {

        $booking_status = $this->getBookingStatus($value);        
        
        $booking_status['is_orphan_booking'] = (string) $value->is_orphan_booking;

        $status = "Pending";
        if($booking_status['pending_start'] == '1')
        {
            $status = "Pending Start";
        }
        else if($booking_status['pending_complete'] == '1')
        {
            $status = "Pending Complete";
        }
        else if($booking_status['is_upcoming'] == '1')
        {
            $status = "Upcoming";
        }
        else if($booking_status['is_on_route'] == '1')
        {
            $status = "On Route";
        }
        else if($booking_status['is_in_progress'] == '1')
        {
            $status = "In progress";
        }
        else if($booking_status['is_completed'] == '1')
        {
            $status = "Completed";
        }
        else if($booking_status['is_cancelled'] == '1')
        {
            $status = "Cancelled";
        }
        else if($booking_status['is_orphan_booking'] == '1')
        {
            $status = "Orphan";
        }
        return $status;
    }

    function getBookingRating($booking_id,$ratings_by,$ratings_for)
    {
        $rating = '';
        $where = ['booking_id'=>$booking_id,'ratings_by'=>$ratings_by,'ratings_for'=>$ratings_for];
        $rating_details = Ratings::where($where)->first();
        
        Log::Info("getBookingRating where: ",$where);
        Log::Info("getBookingRating ".json_encode($rating_details));

        if(!empty($rating_details))
        {
            $rating = $this->ratingFormat($rating_details->ratings);
        }
        return $rating;

    }

}
