<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Config;
use Illuminate\Http\Request;
use Illumintae\Http\SimpleMessage;
use Illuminate\Support\Facades\Hash;
use App\Models\api\v1\Ratings;
use App\Models\api\v1\Token;
use App\Models\api\v1\Users;
use App\Models\api\v1\StripeUserDetails;
use App\Models\api\v1\Myspace;
use Input;
use Auth;
use Mail;
use DB;
use File;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;

use Illuminate\Support\Facades\Route;
//use App\Models\api\v1\Jsons; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Api\v1\StripeUserDetailsController as StripeUserDetailController;

use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{

    public $successStatus = 200;
    public $unauthorizedStatus = 401;
    public $userType = "";
    private $apiToken;

    public function __construct(Request $request){
        // Unique Token
       
        /*** @auther: ALOK => START ***/
       /* $input_data = $request->input();
        $currentPath= Route::getFacadeRoot()->current()->uri();
        $array = ['action' => $currentPath, 'data' => json_encode($input_data), 'call_type' => 'request'];
        $create_json = Jsons::create($array);
        */
        // another way to call error_log():


       
        
        /*** @auther: ALOK => END ***/
        
        $this->apiToken = uniqid(base64_encode(str_random(20)));
        $this->userType = $request->header('userType') ? $request->header('userType') : "";
        $this->userId = $request->header('userId') ? $request->header('userId') : "";
    }
    
    /**
     * ALOK test Api (Not using in application)
     *
     * @return \Illuminate\Http\Response
     */
    public function test(Request $request)
    {
        
        $confirm_link = url('/api/auth/confirmMail');
        // echo "\n--->". $confirm_link;
        
        $user_details_val = Users::where('id',120)->first();

        $send = Mail::send('emails.confirm', ['user' => $user_details_val, 'link'=>$confirm_link], function ($m) use ($user_details_val) {
            $m->from(env("MAIL_SUPPORT"), env("MAIL_FROM"));
            $m->bcc(env("MAIL_SUPPORT"), env("MAIL_FROM"));            
            $name = $user_details_val->first_name.' '.$user_details_val->last_name;
            $m->to($user_details_val->email, $name)->subject('SKEP Home: Confirm email');
        });
        
        
        exit;   
    }

    /**
     * login Api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
                 
        $input = $request->all();
        Log::info('auth login INPUT: '. json_encode($input));
          
        $login_type =  $request->input('social_type');
        if ($login_type == 'facebook' || $login_type == 'gmail') {
            $this->socialLogin($request);

        }
        $this->validation(
            $request->all(),
            [
                "email" => "required|email",
                "password" => "required",
                "device_type" => "required",
                "device_token" => "required",
                'timezone' =>"required",
            ]
        );

        $username = strtolower($request->input('email'));
        $password = $request->input('password');
        
        

        // Customer Login Process
        $useremail = strtolower($request->input('email'));
        $password = $request->input('password');
        $userinfo = Users::select(
            'id as userId',
            'first_name',
            'last_name',
            'email',
            'password',
            'address',
            'country_code',
            'qb_id',
            'phone_number',
            'profile_pic',
            'selfie_image',
            'device_type',
            'device_token',
            'gender',
            'push_notification',
            'latitude',
            'longitude',
            'status',
            'is_email_verified')->where(['email' => $useremail, 'user_type' => $this->userType])->first();
        if(!empty($userinfo)){
            $check_status = $userinfo['is_email_verified'];
            if($check_status == 0){
                $this->error("Please verify your email and login again");
            } else{

                $dbpass = @$userinfo['password'];
                if (!empty($userinfo) && Hash::check(@$password, $dbpass)) {
                    $updated_array = [
                        'device_type' => $request->input('device_type'),
                        'device_token' => $request->input('device_token'),
                        'timezone' => $request->input('timezone')
                    ];

                    $update = Users::where('id',$userinfo['userId'])->update($updated_array);

                    $this->generateToken($userinfo['userId'],$this->userType, $this->apiToken);

                    $user_selfie_image = $userinfo->selfie_image;
                    $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';
                    
                    if($profile_pic == "")
                    {
                        if(!empty($userinfo->profile_pic)){
                            $pic_path = explode('/',$userinfo->profile_pic);
                            $path_count = count($pic_path);
                            if($path_count == 1){
                                $profile_pic = $this->get_user_image_path($userinfo->profile_pic,$this->userType);
                            } else{
                                $profile_pic = $userinfo->profile_pic;
                            }
                        } else{
                            $profile_pic = "";
                        }
                    }

                    $phone_number = $userinfo->phone_number != null ? env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($userinfo->phone_number) : "";
                    

                    $userdetails['user_id'] = $userinfo->userId != null ? $userinfo->userId : "";
                    $userdetails['email'] = $userinfo->email != null ? $userinfo->email : "";
                    $userdetails['first_name'] = $userinfo->first_name != null ? $userinfo->first_name : "";
                    $userdetails['last_name'] = $userinfo->last_name != null ? $userinfo->last_name : "";
                    $userdetails['phone_number'] = $phone_number;
                    $userdetails['qb_id'] = $userinfo->qb_id != null ? $userinfo->qb_id : "";
                    $userdetails['profile_pic'] = $profile_pic;
                    $userdetails['address'] = $userinfo->address != null ? $userinfo->address : "";
                    $userdetails['gender'] = $userinfo->gender != null ? $userinfo->gender : "";
                    $userdetails['country_code'] = $userinfo->country_code != null ? $userinfo->country_code : "";
                    $userdetails['latitude'] = $userinfo->latitude != null ? $userinfo->latitude : "";
                    $userdetails['longitude'] = $userinfo->longitude != null ? $userinfo->longitude : "";
                    $userdetails['push_notification'] = $userinfo->push_notification != null ? $userinfo->push_notification : "";
                    $stripeUserDetails = StripeUserDetails::where(['user_id' => $userdetails['user_id']])->first();
                    if(!empty($stripeUserDetails)){
                      $userdetails['stripe_customer_id'] = $stripeUserDetails->customer_id != null ? $stripeUserDetails->customer_id : "";
                      $userdetails['stripe_card_details'] = $stripeUserDetails->token != null ? unserialize($stripeUserDetails->token) : "";
                      $userdetails['stripe_account_id'] = $stripeUserDetails->account_id != null ? $stripeUserDetails->account_id : "";
                      $userdetails['person_id'] = $stripeUserDetails->person_id != null ? $stripeUserDetails->person_id : "";
                    }
                    $this->success("Login Successfuly.", $userdetails, $this->successStatus, $this->apiToken);

                } else {
                    $this->error("Invalid login details.");
                }
            }
        } else{
            $this->error("User Not Exists");
        }
    }

    /**
     * Customer Social login , Sign up
     *
     * @return response
     */
    
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function signup(Request $request)
    {

        $input = $request->all();
        $fileName = "";
        $fullFilepath = "";
        
        Log::Info(" SIGNUP PARAM: ".json_encode($input));
        
        if ($request->input('social_type') == 'facebook' || $request->input('social_type') == 'gmail') {
            $this->socialLogin($request);

        }
         $this->validation(
            $request->all(),
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'phone_number' => 'required',
                'address' => 'required',
                'country_code' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'email' => 'required|email',
                'password' => 'required',
                'device_type' => 'required',
                'device_token' => 'required',
                'date_of_birth' => 'required|date',
                'timezone' =>'required',
            ]
        );
        $input['date_of_birth'] = date('Y-m-d', strtotime($input['date_of_birth']));
         
        $check_user_exists = Users::where('email', $input['email'])->first();
        if(!empty($check_user_exists))
        {
            if($check_user_exists['user_type'] == $this->userType)
            {
                $check_user_social_id = DB::select("SELECT *  FROM users WHERE id = '".$check_user_exists['id']."' AND social_type != '' AND social_type != ''");
                if(!empty($check_user_social_id))
                {
                    $password = Hash::make($input['password']);
                    $user_type = $this->userType;
                    $dob = date('Y-m-d', strtotime($input['date_of_birth']));

                    $phone_number = $input['phone_number'];
                    $phone_number = $this->cleanPhoneNumber($phone_number);

                    $update = ['first_name' => $input['first_name'], 
                                'last_name' => $input['last_name'], 
                                'phone_number' => $phone_number, 
                                'address' => $input['address'], 
                                'country_code' => $input['country_code'], 
                                'latitude' => $input['latitude'], 
                                'longitude' => $input['longitude'], 
                                'password' => $password, 
                                'device_type' => $input['device_type'], 
                                'device_token' => $input['device_token'], 
                                'date_of_birth' => $dob,
                                'timezone' => $input['timezone'], 
                            ];
                    $update_value = DB::table('users')->where('id',$check_user_exists['id'])->update($update);
                    $user_details_val = Users::where('id',$check_user_exists['id'])->first();
                } 
                else
                {
                    $this->error("Email Already Exists");
                }
            } 
            else
            {
                $this->error("This User already registered with other user type");
            }
            
            
            
            $user_details_value['user_id'] = $user_details_val['id']!= null?$user_details_val['id']:"";
            $user_details_value['email'] = $user_details_val['email']!= null?$user_details_val['email']:"";
            $user_details_value['first_name'] = $user_details_val['first_name']!= null?$user_details_val['first_name']:"";
            $user_details_value['last_name'] = $user_details_val['last_name']!= null?$user_details_val['last_name']:"";
            $user_details_value['phone_number'] = $user_details_val['phone_number']!= null?env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($user_details_val['phone_number']):"";
            $user_details_value['qb_id'] = $user_details_val['qb_id']!= null?$user_details_val['qb_id']:"";
            $user_details_value['user_type'] = $this->userType!= null?$this->userType:"";
            $user_details_value['gender'] = $user_details_val['gender']!= null?$user_details_val['gender']:"";
            $user_details_value['address'] = $user_details_val['address']!= null?$user_details_val['address']:"";
            $user_details_value['country_code'] = $user_details_val['country_code']!= null?$user_details_val['country_code']:"";
            $user_details_value['unique_code'] = $user_details_val['unique_code']!= null?$user_details_val['unique_code']:"";
            $user_details_value['latitude'] = $user_details_val['latitude']!= null?$user_details_val['latitude']:"";
            $user_details_value['longitude'] = $user_details_val['longitude']!= null?$user_details_val['longitude']:"";
            $user_details_value['push_notification'] = $user_details_val['push_notification']!= null?$user_details_val['push_notification']:"";
            
            // Generate Token            
            $this->generateToken($user_details_val['id'],$user_details_val['user_type'], $this->apiToken);

            // Set default rating
            if( $this->userType == "cleaner")
            {
                $ratingExist = Ratings::where(['ratings_for' => $user_details_val['id']])->first();
                if(!$ratingExist){
                    $rating = new Ratings;
                    $rating->ratings_by = 1;
                    $rating->ratings_for =  $user_details_val['id'];
                    $rating->ratings = env("CLEANER_RATING");
                    $rating->save();
                }
            }

            $this->success('Register Successfully', $user_details_value,$this->successStatus,$this->apiToken);
        } 
        else
        {
            $referral_user_id = 0;    
            if(isset($input['referral_code']) && !empty($input['referral_code']))
            {
                $referral_code = $input['referral_code'];                
                $referral_user_type = $this->userType;

                $referral_detail = $this->applyReferralCode($referral_code,$referral_user_type);
                Log::Info("referral_detail: ".json_encode($referral_detail));
                $referral_user_id = $referral_detail['referral_id'];                
                if(!$referral_user_id)
                {
                    $this->error("Invalid referral code");
                }
                else
                {
                    $input['referral_user_id'] = $referral_user_id;
                }
            }
            

            $input['password'] = Hash::make($input['password']);
            $input['user_type'] = $this->userType;
            
            $unique_code = $this->uniquecode();
            $input['unique_code'] = $unique_code;

            $input['status'] = 0;
            $input['authenticate_status'] = 0;
            $input['is_email_verified'] = 0;
            $input['is_phone_number_verified'] = 0;

            $input['work_area'] = env("DEFAULT_WORK_AREA");
            
            if(isset($input['place_id']) && !empty($input['place_id']))
            {
                $place_details = $this->getPlaceDetails($input['place_id']);
                if($place_details['is_error'] == 0)
                {
                    $place_detail = $place_details['place_detail'];
                    if(array_key_exists('city',$place_detail))
                    {
                        $input['city'] = $place_detail['city'];
                    }
                    if(array_key_exists('state',$place_detail))
                    {
                        $input['state'] = $place_detail['state'];
                    }
                    if(array_key_exists('postal_code',$place_detail))
                    {
                        $input['postal_code'] = $place_detail['postal_code'];
                    }
                    if(array_key_exists('country',$place_detail))
                    {
                        $input['country'] = $place_detail['country'];
                    }
                    
                }
                
            }
            
            //Save User details
            $user_details = Users::create($input);
            $user_id = $user_details['id'];            

            /*
            * if user input a valid refer id then receiver and sender earn refer amount 
            * log in db
            */
            if($referral_user_id >0)
            {
                $this->earnReferralAmount($user_id,$referral_user_id,$this->userType);
            }

            //generate new token           
            $this->generateToken($user_id,$this->userType, $this->apiToken);

            $update = ['verify_token' => $this->apiToken];
            $update_value = DB::table('users')->where('id',$user_id)->update($update);
            
            $user_details_val = Users::find($user_id);

            $user_details_value['user_id'] = $user_details_val['id']!= null?$user_details_val['id']:"";
            $user_details_value['email'] = $user_details_val['email']!= null?$user_details_val['email']:"";
            $user_details_value['first_name'] = $user_details_val['first_name']!= null?$user_details_val['first_name']:"";
            $user_details_value['last_name'] = $user_details_val['last_name']!= null?$user_details_val['last_name']:"";
            $user_details_value['phone_number'] = $user_details_val['phone_number']!= null?env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($user_details_val['phone_number']):"";
            $user_details_value['qb_id'] = $user_details_val['qb_id']!= null?$user_details_val['qb_id']:"";
            $user_details_value['user_type'] = $this->userType!= null?$this->userType:"";
            $user_details_value['gender'] = $user_details_val['gender']!= null?$user_details_val['gender']:"";
            $user_details_value['address'] = $user_details_val['address']!= null?$user_details_val['address']:"";
            $user_details_value['country_code'] = $user_details_val['country_code']!= null?$user_details_val['country_code']:"";
            $user_details_value['unique_code'] = $user_details_val['unique_code']!= null?$user_details_val['unique_code']:"";
            $user_details_value['latitude'] = $user_details_val['latitude']!= null?$user_details_val['latitude']:"";
            $user_details_value['longitude'] = $user_details_val['longitude']!= null?$user_details_val['longitude']:"";
            $user_details_value['push_notification'] = $user_details_val['push_notification']!= null?$user_details_val['push_notification']:"";
            $user_details_value['work_area'] = $user_details_val['work_area']!= null?$user_details_val['work_area']:"";

            // Set default rating
            if( $this->userType == "cleaner")
            {
                $ratingExist = Ratings::where(['ratings_for' => $user_details_val['id']])->first();
                if(!$ratingExist){
                    $rating = new Ratings;
                    $rating->ratings_by = 1;
                    $rating->ratings_for =  $user_details_val['id'];
                    $rating->ratings = env("CLEANER_RATING");
                    $rating->save();
                }
            }
            $confirm_link = url('/api/auth/confirmMail?user='.$user_details_val->id.'&usertoken='.$this->apiToken);
           // echo "\n--->". $confirm_link;
            
            $send = Mail::send('emails.confirm', ['user' => $user_details_val, 'link'=>$confirm_link], function ($m) use ($user_details_val) {
                $m->from(env("MAIL_SUPPORT"), env("MAIL_FROM"));
                $m->bcc(env("MAIL_SUPPORT"), env("MAIL_FROM"));
                //echo "\n====".$user_details_val->email.'================'.env("MAIL_SUPPORT");
                $name = $user_details_val->first_name.' '.$user_details_val->last_name;
                $m->to($user_details_val->email, $name)->subject('SKEP Home: Confirm email');
              });
              
            Log::Info("======================\nauth/confirmMail". json_encode($send));

            $this->success('Register Successfully', $user_details_value,$this->successStatus,$this->apiToken);
        }
    }


    public function checkUserExistsWithSocial(Request $request){
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                "email" => "required",
                "social_type" => "required",
                "social_id" => "required",
                "device_type" => "required",
                "device_token" => "required"
            ]
        );
        $check_user_exists = Users::where('email', $input['email'])->first();
        if(!empty($check_user_exists)){
            if($check_user_exists['user_type'] == $this->userType){
                $update = ['social_type' => $input['social_type'],'social_id' => $input['social_id'], 'device_type' => $input['device_type'], 'device_token' => $input['device_token']];
                $values = DB::table('users')->where('id',$check_user_exists['id'])->update($update);
                $user_details = Users::where('id',$check_user_exists['id'])->first();

                $user_selfie_image = $user_details['selfie_image'];
                $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

                if($profile_pic == "")
                {
                    if(!empty($user_details['profile_pic'])){
                        $pic_path = explode('/',$user_details['profile_pic']);
                        $path_count = count($pic_path);
                        if($path_count == 1){
                            $profile_pic = $this->get_user_image_path($user_details['profile_pic'],$user_details['user_type']);
                        } else{
                            $profile_pic = $user_details['profile_pic'];
                        }
                    } else{
                        $profile_pic = "";
                    }    
                }

                $this->generateToken($user_details['id'],$user_details['user_type'], $this->apiToken);
                
                $users_values['user_id'] = $user_details['id'];
                $users_values['email'] = $user_details['email'];
                $users_values['first_name'] = (string) @$user_details['first_name'];
                $users_values['last_name'] = (string) @$user_details['last_name'];
                $users_values['phone_number'] = (string)($user_details['phone_number']!= null)?env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($user_details['phone_number']):"";
                $users_values['qb_id'] = (string) @$user_details['qb_id'];
                $users_values['social_type'] = (string) @$user_details['social_type'];
                $users_values['social_id'] = (string) @$user_details['social_id'];
                $users_values['gender'] = (string) @$user_details['gender'];
                $users_values['profile_pic'] = $profile_pic;
                $users_values['user_type'] = (string) @$user_details['user_type'];
                $users_values['address'] = (string) @$user_details['address'];
                $users_values['latitude'] = (string) @$user_details['latitude'];
                $users_values['longitude'] = (string) @$user_details['longitude'];
                $users_values['country_code'] = (string) @$user_details['country_code'];
                $users_values['unique_code'] = (string) @$user_details['unique_code'];

                $this->success("User Exists here",$users_values,$this->successStatus,$this->apiToken);
            } else{
                $this->error("This User already registered with other user type");
            }
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Change password api */

    public function changePassword(Request $request){

        $input = $request->all();

        $this->validation(
            $request->all(),
            [
                "old_password" => "required",
                "new_password" => "required",
                "confirm_password" => "required|same:new_password"
            ]
        );
        $check_user_exists = Users::where('id',$this->userId)->first();

        if(!empty($check_user_exists)){
            if(Hash::check($request->input("old_password"),$check_user_exists->password)){
                $password = Hash::make($request->input("new_password"));
                $check_user_exists->password = $password;
                $check_user_exists->save();
                $this->success("Password changed successfully","");
            } else{
                $this->error("Password doesn't match");
            }
        } else{
            $this->error('User Not Found');
        }
    }

    /* Forgot Password api */

    public function forgotPassword(Request $request){

        $input = $request->all();

        $this->validation(
            $request->all(),
            [
                "email" => "required|email"
            ]
        );

        $user_details = Users::where('email',$request->input("email"))->first();
        
        Log::Info(" User detail : ".json_encode($user_details));
        
        if(!empty($user_details)){
           $pswd_string = "abcdefghijklmnopqrstuvwxyz0123456789";

            $verify_token = sha1(substr(str_shuffle(str_repeat($pswd_string, 8)), 0, 8));
            $user_details->remember_token = (string) $verify_token;
            if($user_details->save()){
                $pass_url = url('/auth/resetPass?user='.$user_details->id.'&usertoken='.$verify_token);
                $send = Mail::send('emails.forgot_password', ['username' => ($user_details->first_name) ? $user_details->first_name : 'User', 'pass_url'=>$pass_url], function ($m) use ($user_details) {
                $m->from(env("MAIL_SUPPORT"), env("MAIL_FROM"));
                $m->to($user_details->email, $user_details->first_name)->subject('SKEP Home: Reset Password');
                });
                $this->success('A link to reset your password has been sent to your email.', '');
                } else {
                  $this->error('Please try again.');
                }
        } else{
            $this->error("User not found");
        }
    }

    

    public function socialLogin(Request $request)
    {

        $this->validation(
            $request->all(),
            [
                "email" => "required|email",
                "social_id" => "required",
                "social_type" => "required",
                "device_type" => "required",
                "device_token" => "required",
                "timezone" =>"required",
            ]
        );

        $useremail = strtolower($request->input('email'));

        $userinfo = Users::select(
            'id as user_id',
            'first_name',
            'last_name',
            'email',
            'address',
            'country_code',
            'qb_id',
            'phone_number',
            'user_type',
            'social_id',
            'profile_pic',
            'selfie_image',
            'device_type',
            'device_token',
            'push_notification',
            'latitude',
            'longitude',
            'status'
        )->where(['email' => $useremail, 'user_type' => $this->userType])->first();
        Log::Info(" user info : ".json_encode($userinfo));
        if (!empty($userinfo)) {
            if(!empty($userinfo->social_id)){

                $this->generateToken($userinfo->user_id,$this->userType, $this->apiToken);
                
                $phone_number = $this->cleanPhoneNumber($request->input('phone_number'));

                $userinfo->device_type = $request->input('device_type');
                $userinfo->device_token = $request->input('device_token');
                $userinfo->social_type = $request->input('social_type');
                $userinfo->user_type = $this->userType;
                $userinfo->first_name = $request->input('first_name');
                $userinfo->last_name = $request->input('last_name');
                $userinfo->phone_number = $phone_number;
                $userinfo->latitude = $request->input('latitude');
                $userinfo->longitude = $request->input('longitude');                
                if($request->input('profile_pic')){
                    $userinfo->profile_pic = $request->input('profile_pic');
                }
                $userinfo->timezone = $request->input('timezone');
                $userinfo->save();

                $phone_number = $userinfo->phone_number != null ? env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($userinfo->phone_number) : "";
                $userdetails['user_id'] = $userinfo->user_id != null ? $userinfo->user_id : "";
                $userdetails['email'] = $userinfo->email != null ? $userinfo->email : "";
                $userdetails['first_name'] = $userinfo->first_name != null ? $userinfo->first_name : "";
                $userdetails['last_name'] = $userinfo->last_name != null ? $userinfo->last_name : "";
                $userdetails['phone_number'] = $phone_number;
                $userdetails['qb_id'] = $userinfo->qb_id != null ? $userinfo->qb_id : "";
                $userdetails['social_type'] = $userinfo->social_type != null ? $userinfo->social_type : "";
                $userdetails['social_id'] = $userinfo->social_id != null ? $userinfo->social_id : "";
                $userdetails['user_type'] = $userinfo->user_type != null ?$userinfo->user_type:"";
                $userdetails['gender'] = $userinfo->gender != null ?$userinfo->gender:"";
                $userdetails['address'] = $userinfo->address != null ? $userinfo->address : "";
                $userdetails['country_code'] = $userinfo->country_code != null ? $userinfo->country_code : "";
                $userdetails['latitude'] = $userinfo->latitude != null ? $userinfo->latitude : "";
                $userdetails['longitude'] = $userinfo->longitude != null ? $userinfo->longitude : "";
                $userdetails['push_notification'] = $userinfo->push_notification != null ? $userinfo->push_notification : "";
                if($userinfo->social_id = ''){
                    $userdetails['profile_pic'] = $userinfo->profile_pic != null ? $this->get_user_image_path($userinfo->profile_pic, $this->userType) : "";
                } else{
                    $userdetails['profile_pic'] = $userinfo->profile_pic != null ? $userinfo->profile_pic : "";
                }
                $userdetails['status'] = $userinfo->status != null ? $userinfo->status : 1;
                $userdetails['authenticate_status'] = $userinfo->authenticate_status != null ? $userinfo->authenticate_status : 1;
                $userdetails['is_email_verified'] = $userinfo->is_email_verified;
                // Set default rating
                if( $this->userType == "cleaner"){
                    $ratingExist = Ratings::where(['ratings_for' => $userinfo->user_id])->first();
                    if(!$ratingExist){
                        $rating = new Ratings;
                        $rating->ratings_by = 1;
                        $rating->ratings_for =  $userinfo->user_id;
                        $rating->ratings = env("CLEANER_RATING");
                        $rating->save();
                    }
                }

                $this->success("Login successfuly.", $userdetails, $this->successStatus, $this->apiToken);
            } else{
                $this->error("This Email Id already registered");
            }
        } else {

            $this->validation($request->all(), [
                "email" => "required|email|unique:users",
            ]);

            $input = $request->all();            

            $referral_user_id = 0;    
            if(isset($input['referral_code']) && !empty($input['referral_code']))
            {

                $referral_code = $input['referral_code'];                
                $referral_user_type = $this->userType;

                $referral_detail = $this->applyReferralCode($referral_code,$referral_user_type);
                $referral_user_id = $referral_detail['referral_id'];                
                if(!$referral_user_id)
                {
                    $this->error("Invalid referral code");
                }
                else
                {
                    $input['referral_user_id'] = $referral_user_id;
                }
               
            }
            
            $input['user_type'] = $this->userType;
            
            $unique_code = $this->uniquecode();
            $input['unique_code'] = $unique_code;

            $input['status'] = 0;
            $input['authenticate_status'] = 0;
            $input['is_email_verified'] = 1;
            $input['is_phone_number_verified'] = 0;
            $input['work_area'] = env("DEFAULT_WORK_AREA");

            if(isset($input['place_id']) && !empty($input['place_id']))
            {
                $place_details = $this->getPlaceDetails($input['place_id']);
                if($place_details['is_error'] == 0)
                {
                    $place_detail = $place_details['place_detail'];
                    if(array_key_exists('city',$place_detail))
                    {
                        $input['city'] = $place_detail['city'];
                    }
                    if(array_key_exists('state',$place_detail))
                    {
                        $input['state'] = $place_detail['state'];
                    }
                    if(array_key_exists('postal_code',$place_detail))
                    {
                        $input['postal_code'] = $place_detail['postal_code'];
                    }
                    if(array_key_exists('country',$place_detail))
                    {
                        $input['country'] = $place_detail['country'];
                    }
                    
                }
                
            }
            $user_details = Users::create($input);
            $user_id = $user_details['id'];

            if($this->userType == 'homeOwner')
            {
                $res = StripeUserDetailController::generateStripeCustomerId($user_id);                 
                
            }
            else
            {
                $res = StripeUserDetailController::generateStripeRecipientFromBackendSignup($user_id); 
                if($res != true)
                {
                    $this->error($res);
                }
            } 
            
            /*** Earn referral amount to the receiver and sender */
            if($referral_user_id >0)
            {
                $this->earnReferralAmount($user_id,$referral_user_id,$this->userType);
            }

            $user_selfie_image = $user_details['selfie_image'];
            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';

            if($profile_pic == "")
            {
                if(!empty($user_details['profile_pic'])){
                    $pic_path = explode('/',$user_details['profile_pic']);
                    $path_count = count($pic_path);
                    if($path_count == 1){
                        $profile_pic = $this->get_user_image_path($user_details['profile_pic'],$this->userType);
                    } else{
                        $profile_pic = $user_details['profile_pic'];
                    }
                } else{
                    $profile_pic = "";
                }    
            } 

            $data = Users::where('id',$user_id)->first();
            $data_val['user_id'] = (!empty($data['id']))?$data['id']:"";
            $data_val['email'] = (!empty($data['email']))?$data['email']:'';
            $data_val['first_name'] = (!empty($data['first_name']))?$data['first_name']:"";
            $data_val['last_name'] = (!empty($data['last_name']))?$data['last_name']:"";
            $data_val['phone_number'] = (!empty($data['phone_number']))?env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($data['phone_number']):"";
            $data_val['qb_id'] = (!empty($data['qb_id']))?$data['qb_id']:"";
            $data_val['social_type'] = (!empty($data['social_type']))?$data['social_type']:"";
            $data_val['social_id'] = (!empty($data['social_id']))?$data['social_id']:"";
            $data_val['gender'] = (!empty($data['gender']))?$data['gender']:"";
            $data_val['profile_pic'] = $profile_pic;
            $data_val['user_type'] = (!empty($data['user_type']))?$data['user_type']:"";
            $data_val['address'] = (!empty($data['address']))?$data['address']:"";
            $data_val['latitude'] = (!empty($data['latitude']))?$data['latitude']:"";
            $data_val['longitude'] = (!empty($data['longitude']))?$data['longitude']:"";
            $data_val['push_notification'] = (!empty($data['push_notification']))?$data['push_notification']:"";
            $data_val['unique_code'] = (!empty($data['unique_code']))?$data['unique_code']:"";

            $data_val['status'] =  (!empty($data['status']))?$data['status']:"";
            $data_val['authenticate_status'] =  (!empty($data['authenticate_status']))?$data['authenticate_status']:"";
            $data_val['is_email_verified'] = $data['is_email_verified'];
            
            
            //
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $data['user_id']])->first();
            if(!empty($stripeUserDetails)){
                $data_val['stripe_customer_id'] = $stripeUserDetails->customer_id != null ? $stripeUserDetails->customer_id : "";
                $data_val['stripe_card_details'] = $stripeUserDetails->token != null ? unserialize($stripeUserDetails->token) : "";
                $data_val['stripe_account_id'] = $stripeUserDetails->account_id != null ? $stripeUserDetails->account_id : "";
                $data_val['person_id'] = $stripeUserDetails->person_id != null ? $stripeUserDetails->person_id : "";
            }
            //

            // Generate Token
           $this->generateToken($user_id,$this->userType, $this->apiToken);
            
            // Set default rating
                if( $this->userType == "cleaner"){
                    $ratingExist = Ratings::where(['ratings_for' => $user_id])->first();
                    if(!$ratingExist){
                        $rating = new Ratings;
                        $rating->ratings_by = 1;
                        $rating->ratings_for =  $user_id;
                        $rating->ratings = env("CLEANER_RATING");
                        $rating->save();
                    }
                }
                
            Log::Info("Signup response: ".json_encode($data_val));
            $this->success('Login Successfully',$data_val,$this->successStatus,$this->apiToken);

        }
    }

    /* Edit Profile api */
    public function editProfile(Request $request){
        $input = $request->all();

        Log::Info("editProfile : ".json_encode($input));


        $user_details = Users::where('id',$this->userId)->first();
        if(!empty($user_details)){
            if(!empty($request['profile_pic'])){
                if($this->userType == 'homeOwner'){
                   if(!empty($user_details->profile_pic)){
                        if(\File::exists(public_path('images/users/homeowners'.$user_details->profile_pic))){
                          \File::delete(public_path('images/users/homeowners'.$user_details->profile_pic));
                        }
                    }
                } else if($this->userType == 'cleaner'){
                    if(!empty($user_details->profile_pic)){
                        if(\File::exists(public_path('images/users/cleaners'.$user_details->profile_pic))){
                          \File::delete(public_path('images/users/cleaners'.$user_details->profile_pic));
                        }
                    }
                }
                $imageName = time().'.'.$input['profile_pic']->getClientOriginalExtension();
                if($this->userType == 'homeOwner'){
                    $image_name = $request->profile_pic->move(public_path('images/users/homeowners'), $imageName);
                    //
                    // $profile_pic = $request->file('profile_pic');                    
                    //Storage::disk('public')->put('/users/homeowners/' . $imageName,  File::get($profile_pic));
                    //

                } else if($this->userType == 'cleaner'){
                    //$image_name = $request->profile_pic->move(public_path('images/users/cleaners'), $imageName);
                    $profile_pic = $request->file('profile_pic');                    
                    Storage::disk('local')->put('/users/homeowners/' . $imageName,  File::get($profile_pic));
                }
                $image_val = $imageName;
                $user_details->profile_pic = $image_val;
                $user_details->save();
            }

            
            $prev_address = $user_details->address;
            $prev_first_name = $user_details->first_name;
            $prev_last_name = $user_details->last_name;
            $prev_phone_number = $user_details->phone_number;
            $prev_date_of_birth = $user_details->date_of_birth;

            $address = !empty($request->input('address'))?$request->input('address'):$user_details->address;                

            if($this->userType == 'homeOwner'){
                if(!empty($request->input('space_id')))
                {
                    
                    DB::table('my_space')->where('id','<>',$input['space_id'])->update(['set_as_default' => 0]);
                    DB::table('my_space')->where('id',$input['space_id'])->update(['set_as_default' => 1]);
                                    
                    $space_detail = Myspace::where('id',$input['space_id'])->first();
                    $address = $space_detail->address;                
                }
                
            }
            
            $user_details->first_name = !empty($request->input('first_name'))?$request->input('first_name'):$user_details->first_name;
            $user_details->last_name = !empty($request->input('last_name'))?$request->input('last_name'):$user_details->last_name;
            $user_details->date_of_birth = !empty($request->input('date_of_birth'))?$request->input('date_of_birth'):$user_details->date_of_birth;
            $user_details->gender = !empty($request->input('gender'))?$request->input('gender'):$user_details->gender;
            $user_details->phone_number = !empty($request->input('phone_number'))?$this->cleanPhoneNumber($request->input('phone_number')):$this->cleanPhoneNumber($user_details->phone_number);
            $user_details->country_code = !empty($request->input('country_code'))?$request->input('country_code'):$user_details->country_code;
            $user_details->address = $address;
            $user_details->sin_number = !empty($request->input('sin_number'))?$request->input('sin_number'):$user_details->sin_number;
         
   
            $latitude = !empty($input['latitude'])?$input['latitude']:'';
            $longitude = !empty($input['longitude'])?$input['longitude']:'';

            $address_latitude = !empty($input['address_latitude'])?$input['address_latitude']:'';
            $address_longitude = !empty($input['address_longitude'])?$input['address_longitude']:'';

            $user_details->latitude = $latitude;
            $user_details->longitude = $longitude;
            $user_details->address_latitude = $address_latitude;
            $user_details->address_longitude = $address_longitude;
            

            if(isset($input['place_id']) && !empty($input['place_id']))
            {
                $place_details = $this->getPlaceDetails($input['place_id']);
                if($place_details['is_error'] == 0)
                {
                    $place_detail = $place_details['place_detail'];
                    if(array_key_exists('city',$place_detail))
                    {
                        $user_details->city = $place_detail['city'];
                    }
                    if(array_key_exists('state',$place_detail))
                    {
                        $user_details->state = $place_detail['state'];
                    }
                    if(array_key_exists('postal_code',$place_detail))
                    {
                        $user_details->postal_code = $place_detail['postal_code'];
                    }
                    if(array_key_exists('country',$place_detail))
                    {
                        $user_details->country = $place_detail['country'];
                    }
                    
                }
                
            }

            if($user_details->save()){

                $get_user_data = Users::where('id',$this->userId)->first();
                $response = array(
                    'first_name' => (string) $get_user_data->first_name,
                    'last_name' => (string) $get_user_data->last_name,
                    'email' => (string) $get_user_data->email,
                    'date_of_birth' => (string) $get_user_data->date_of_birth,
                    'gender' => (string) $get_user_data->gender,
                    'phone_number' => (string) $get_user_data->phone_number != null ? env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($get_user_data->phone_number) : "",
                    'country_code' => (string) $get_user_data->country_code,
                    'sin_number' => (string) $get_user_data->sin_number,
                    'profile_pic' => $this->get_user_image_path($get_user_data->profile_pic, $this->userType),
                    'address' => (string) $get_user_data->address,
                
                );                

                $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();

                if($this->userType == 'cleaner' && $stripeUserDetails && !empty($stripeUserDetails->account_id))
                {
                    $stripe_update = [];
                    $stripe_update['phone'] = $get_user_data->phone_number;
                    $address = [];
                    $address['line1'] = $get_user_data->address;

                    if(!empty($get_user_data->city))
                    {
                        $address['city'] = $get_user_data->city;
                    }
                    if(!empty($get_user_data->state))
                    {
                        $address['state'] = $get_user_data->state;
                    }
                    if(!empty($get_user_data->country))
                    {
                        $address['country'] = $get_user_data->country;
                    }
                    if(!empty($get_user_data->postal_code))
                    {
                        $address['postal_code'] = $get_user_data->postal_code;
                    }

                    $stripe_update['address'] = $address;

                    $account_id = $stripeUserDetails->account_id;
                    $person = StripeUserDetailController::cleanerUpdateIndividual($account_id,$stripe_update);
                    if(!is_array($person))
                    {
                        $this->error($person);
                    }
                    $personId = $person['id'];
                    $stripeUserDetails->person_id = $personId;
                    $stripeUserDetails->save();                            
                    $response['person'] = $person;
                    
                }                               
                               
                $this->success("User Update Successfully",$response,$this->successStatus,$this->apiToken);

            } else{
                $this->error("User not updated");
            }
        } else{
            $this->error("User not found");
        }
    }

    /* Get Profile api */

    public function getProfile(Request $request){
        $input = $request->all();
        $user_details = Users::where('id',$this->userId)->first();

        Log::Info("getProfile Input: ".json_encode($input));

        $address = '';
        if(!empty($user_details)){

            
            $stripeUserDetails = StripeUserDetails::where(['user_id' => $this->userId])->first();
            $userDetails = [];
            if(!empty($stripeUserDetails)){
              $userDetails['stripe_customer_id'] = $stripeUserDetails->customer_id != null ? $stripeUserDetails->customer_id : "";
              $userDetails['stripe_card_details'] = $stripeUserDetails->token != null ? unserialize($stripeUserDetails->token) : "";
              $userDetails['stripe_account_id'] = $stripeUserDetails->account_id != null ? $stripeUserDetails->account_id : "";
              $userDetails['person_id'] = $stripeUserDetails->person_id != null ? $stripeUserDetails->person_id : "";
            }
            $address = $user_details->address;
            if($user_details->user_type == 'homeOwner')
            {
                $array = ['user_id' => $this->userId, 'set_as_default' =>1];
                $home_owner_default_space = Myspace::where($array)->first();
                Log::Info("home_owner_default_space OUTPUT: ".json_encode($home_owner_default_space));
                if($home_owner_default_space)
                {
                    $address = $home_owner_default_space['address'];
                }
            }
            
            
            $user_selfie_image = $user_details->selfie_image;
            $profile_pic = (!empty($user_selfie_image))?$this->get_authenticate_certificate($user_selfie_image,'selfie_verification'):'';                
            
            if($profile_pic == "")
            {
                if (!empty($user_details->profile_pic)) 
                {
                    $pic_path = explode('/', $user_details->profile_pic);
                    $path_count = count($pic_path);
                    if ($path_count == 1) 
                    {
                        $profile_pic = $this->get_user_image_path($user_details->profile_pic, $this->userType);
                    } 
                    else 
                    {
                        $profile_pic = $user_details->profile_pic;
                    }
                } 
                else 
                {
                    $profile_pic = "";
                }
            }
            $profile_pic = $this->get_user_image_path($user_details->profile_pic, $this->userType);            
            $response = [
                'first_name' => (string) $user_details->first_name,
                'last_name' => (string) $user_details->last_name,
                'email' => (string) $user_details->email,
                'date_of_birth' => (string) $user_details->date_of_birth,
                'gender' => (string) $user_details->gender,
                'phone_number' => (string) $user_details->phone_number != null ? env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($user_details->phone_number) : "",
                'country_code' => (string) $user_details->country_code,
                'sin_number' => (string) $user_details->sin_number,
                'profile_pic' => (string) $profile_pic,
                'address' => (string) $address,
                'unique_code' => (string) $user_details->unique_code,
                'referral_balance' => (string) $this->amountToFloat($user_details->referral_balance)
            ];
            $response = array_merge($response,$userDetails);

            Log::Info("getProfile OUTPUT: ".json_encode($response));
        

            $this->success("User Found Successfully",$response,$this->successStatus,$this->apiToken);
        } else{
            $this->error("User not found");
        }
    }

    /* Enable Push notification option */

    public function changePushNotification(Request $request){

        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'notification_status' => 'required'
            ]
        );

        Log::Info("changePushNotification: ".json_encode($input));
        
        $check_user_exists = Users::where('id',$this->userId)->first();
        if(!empty($check_user_exists)){
            $update = ['push_notification' => $input['notification_status']];
            $change_status = DB::table('users')->where('id',$this->userId)->update($update);
            $this->success("Push Notification Status Updated","");
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Enable Online/Offline Status of User */

    public function changeCleanerWorkStatus(Request $request){

        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'work_status' => 'required'
            ]
        );
        $check_user_exists = Users::where('id',$this->userId)->first();
        if(!empty($check_user_exists)){
            $update = ['work_status' => (int)$input['work_status']];
            $change_status = DB::table('users')->where('id',$this->userId)->update($update);
            $this->success("Work Status Updated","");
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Logout api */

    public function logout(Request $request){
        $user =  Users::where('id',$this->userId)->first();
        if (!empty($user)) {
            $user->device_type = '';
            $user->device_token = '';
            if($user->save()){
                $this->success('Logout successfully.', "");
            }else{
                $this->error('Something went wrong.');
            }
        }else{
            $this->error('User does not exists.');
        }
    }

    /* Function to add region_area for work of cleaner */

    public function saveWorkAreaRegion(Request $request){
        $input = $request->all();

        Log::Info("============++++=================");        
        Log::Info("saveWorkAreaRegion : ".json_encode($input));
        Log::Info("============++++=================");


        $where = ['id' => $this->userId, 'user_type' => $this->userType];
        
        $check_user_exists = Users::where($where)->first();
        if(!empty($check_user_exists)){
            $values = ['work_area' => $input['work_area_region']];
            $update = DB::table('users')->where('id',$check_user_exists['id'])->update($values);
            $this->success("Work Regions added successfully","");
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Function to get the selected area of work */

    public function userSavedRegion(Request $request){
        $input = $request->all();
        $where = ['id' => $this->userId, 'user_type' => $this->userType];
        $check_user_exists = Users::where($where)->first();
        if(!empty($check_user_exists)){
            $region_ids = (string) @$check_user_exists->work_area;
            $this->success("Work Regions Found",$region_ids);
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Function to verfiy cleaner Government Id */

    public function authenticateGovernmentIdCertificate(Request $request){


        $this->validation(
            $request->all(),
            [
                "government_id_front" => "required",
                "government_id_back" => "required"
            ]
        );
        // Newly Uploaded files
        $imageFront = $request->file('government_id_front');
        $imageBack = $request->file('government_id_back');
        $input['government_id_image_front'] = rand(1111,9999).time().'.'.$imageFront->getClientOriginalExtension();
        $input['government_id_image_back'] = rand(0,999).time().'.'.$imageBack->getClientOriginalExtension();


        //Log::Info("authenticateGovernmentIdCertificate FILE".json_encode($request->file));

        Log::Info("authenticateGovernmentIdCertificate INPUT".json_encode($input));


        $where = ['id' => $this->userId, 'user_type' => $this->userType, 'account_blocked' => 0];
        $check_user_exists = Users::where($where)->first();
        // Checking if front file already exists then deleting it
        if(!empty($check_user_exists)){
            if(!empty($check_user_exists['government_id_image_front'])){
                $image_path = "/images/authentication_certificates/".$check_user_exists['government_id_image_front'];
                if(File::exists(public_path($image_path))) {
                    File::delete(public_path($image_path));
                }
            }
            // Condition for back file check
            if(!empty($check_user_exists['government_id_image_back'])){
                $image_path = "/images/authentication_certificates/".$check_user_exists['government_id_image_back'];
                if(File::exists(public_path($image_path))) {
                    File::delete(public_path($image_path));
                }
            }
            
            $destinationPath = public_path('/images/authentication_certificates');
            $imageFront->move($destinationPath, $input['government_id_image_front']);
            $imageBack->move($destinationPath, $input['government_id_image_back']);
            $update = DB::table('users')->where('id',$this->userId)->update(['government_id_image_front' => $input['government_id_image_front'], 'government_id_image_back' => $input['government_id_image_back']]);
            if($update == 1){
                if($check_user_exists['authenticate_status'] == 0)
                {
                    $this->updateAuthStatus($this->userId);
                }
                
                $this->success("Your government document is securely uploaded! We will verify and notify you shortly!","");
            } else{
                $this->error("Something Went Wrong");
            }
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Function to authenticate user selfie image */

    public function authenticateSelfie(Request $request){

        $this->validation(
            $request->all(),
            [
                "selfie" => "required"
            ]
        );

        $where = ['id' => $this->userId, 'user_type' => $this->userType, 'account_blocked' => 0];
        $check_user_exists = Users::where($where)->first();
        if(!empty($check_user_exists)){
            if(!empty($check_user_exists['selfie_image'])){

                $message = 'Selfie updated successfully';
                $image_path = "/images/selfie_verification/".$check_user_exists['selfie_image'];
                if(File::exists(public_path($image_path))){
                    File::delete(public_path($image_path));
                }
            }
            else
            {
                $message = 'Selfie uploaded successfully';   
            }
            $image = $request->file('selfie');
            $input['selfie'] = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images/selfie_verification');
            $image->move($destinationPath, $input['selfie']);
            
            $update = DB::table('users')->where('id',$this->userId)->update(['status'=>'0','selfie_image' => $input['selfie']]);
            
            if($check_user_exists['authenticate_status'] == 0)
            {
                $this->updateAuthStatus($this->userId);
            }            
            
            if($update == 1){
                $this->success($message,"");
            } else{
                $this->error("Something Went Wrong");
            }
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Function to verify the phone number status */

    public function verifyPhoneNumber(Request $request){
        $input = $request->all();
        $this->validation(
            $request->all(),
            [
                'country_code' => 'required',
                'phone_number' => 'required'
            ]
        );

        $check_user_exists = Users::where('id',$this->userId)->first();
        if(!empty($check_user_exists)){
            $phone_number  = $this->cleanPhoneNumber($input['phone_number']);
            $update = DB::table('users')->where('id',$this->userId)->update(['phone_number' => $phone_number, 'is_phone_number_verified' => '1', 'country_code' => $input['country_code']]);
            if($update == 0 || $update == 1){
                $this->success("Phone Status Updated","");
            } else{
                $this->error("Something went Wrong");
            }
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Function to get all authenticate certificate */

    public function getAuthenticateCertificate(Request $request){

        

        $where = ['id' => $this->userId, 'user_type' => $this->userType];
        $check_user_exists = Users::where($where)->first();
        if(!empty($check_user_exists)){
            $govt_id_front = $govt_id_back = "";
            if(!empty($check_user_exists['government_id_image_front']))
            {
                $govt_id_front = $this->get_authenticate_certificate($check_user_exists['government_id_image_front'],'authentication_certificates');
            }
            if(!empty($check_user_exists['government_id_image_back']))
            {
                $govt_id_back = $this->get_authenticate_certificate($check_user_exists['government_id_image_back'],'authentication_certificates');
            }
            $selfie_image = (!empty($check_user_exists['selfie_image']))?$this->get_authenticate_certificate($check_user_exists['selfie_image'],'selfie_verification'):"";
            $user_authentication_status = [
                'id' => $this->userId,
                'first_name' => (string) @$check_user_exists['first_name'],
                'last_name' => (string) @$check_user_exists['last_name'],
                'phone_number' => (string) $check_user_exists['phone_number'] != null ? env('SMS_COUNTRY_CODE').$this->cleanPhoneNumber($check_user_exists['phone_number']) : "",
                'is_phone_number_verified' => (string) @$check_user_exists['is_phone_number_verified'],
                'email' => (string) @$check_user_exists['email'],
                'is_email_verified' => (string)$check_user_exists['is_email_verified'],
                'government_id_image_front' => $govt_id_front,
                'government_id_image_back' => $govt_id_back,
                'selfie_image' => $selfie_image,
                'authenticate_status' => (string) @$check_user_exists['authenticate_status'],
                'status' => (string) @$check_user_exists['status']
            ];
            
            Log::Info("getAuthenticateCertificate RESPONSE: ".json_encode($user_authentication_status));
            
            if(!empty($user_authentication_status)){
                $this->success("Authentication Certifications Found",$user_authentication_status);
            } else{
                $this->error("Authentication Certifications Not Found");
            }
        } else{
            $this->error("User Not Exists");
        }
    }

    

    /* Function to confirm email after registeration */

    public function confirmMail(Request $request) {
        
        $this->validation($request->all(), [
               "user" => "required",
               "usertoken" => "required",
           ]);

        $user_id = $request->input('user');
        $usertoken = $request->input('usertoken');

        $check_token =  Users::where(['id'=>$user_id, 'verify_token'=>$usertoken])->first();      
       if ($check_token) 
       {
              
        if($check_token->user_type == 'homeOwner')
        {
            $res = StripeUserDetailController::generateStripeCustomerId($user_id); 
                                   
        }
        else
        {
            $res = StripeUserDetailController::generateStripeRecipientFromBackendSignup($user_id); 
            if($res != true)
            {
                //$this->error($res);
                echo "<center><p style='font-size:40px;color:red'>".$res.".</p></center>";
            }

        } 
        
        
        $update =  Users::where(['id'=>$user_id, 'verify_token'=>$usertoken])
                    ->update(['verify_token'=>'','is_email_verified'=>1]);
        echo "<center><p style='font-size:40px;'>Congratulations, your email has been verified.</p></center>";
       }else{
        echo "<center><p style='font-size:40px;'>This link is not valid.</p></center>";
       }
    }

    /* Update Quickblock id of user */

    public function updateQuickBlockId(Request $request){
        $input = $request->all();
        $this->validation($request->all(),
            [
                'qb_id' => 'required'
            ]
        );
        $check_user_exists = Users::where('id',$this->userId)->first();
        if(!empty($check_user_exists)){
            $update = DB::table('users')->where('id',$this->userId)->update(['qb_id' => $input['qb_id']]);
            $this->success("Quick Block Id Updated Successfully","");
        } else{
            $this->error("User Not Exists");
        }
    }

    /* Function to create random referral code */

    public function random_num($size = ''){
        $alpha_key = '';
        $keys = range('A', 'Z');

        for ($i = 0; $i < 2; $i++) {
            $alpha_key .= $keys[array_rand($keys)];
        }

        $length = $size - 2;

        $key = '';
        $keys = range(0, 9);

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }

        return $alpha_key . $key;
    }

    /* Function to get user image */

    public function get_user_image_path($image_name, $user_type){
        $image_path = '';
        if(!empty($image_name)){
            if($user_type == 'homeOwner'){
                $image_path = url('/').'/public/images/users/homeowners/'.$image_name;
            } else if($user_type == 'cleaner'){
                
                //$image_path = url('/').'/public/images/users/cleaners/'.$image_name;
                
                $path = storage_path('uploads/users/homeowners/' . $image_name);
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                return $base64;
                
            }
        } else{
            $image_path = '';
        }
        return $image_path;
    }


    /* Api function to send OTP for phone verification (cleaner/client)
    * param: phone_number (with country code)
    */
    public function sendPhoneOtp(Request $request)
    {
        $input = $request->all();

        $this->validation(
            $request->all(),
            [
                'phone_number' => 'required',
            ]
        );
        $check_user_exists = Users::where('id', $this->userId)->first();
        /* Check user exists */
        if (!empty($check_user_exists)) {
            
            if(env("SMS_TEST_OTP") == 1)
            {
                $phone_verification_code = env("SMS_TEST_STATIC_OTP");
            }
            else
            {
                $phone_verification_code = $this->uniquecode_nunber();
            }

            $otp_expire = date("Y-m-d H:i:s", strtotime(env("PHONE_VERIFICATION_OTP_EXPIRE").' minutes', strtotime(date('Y-m-d H:i:s'))));
            $phone_number = $input['phone_number'];
            $phone_number = $this->cleanPhoneNumber($phone_number);

            $sms_content = "Your verification code for the Skep Home account is: ".$phone_verification_code;
            
            $update_value = ['phone_otp_expire' => $otp_expire,'phone_verification_code' => $phone_verification_code,'phone_number' => $phone_number];
            $updated = DB::table('users')->where('id', $check_user_exists['id'])->update($update_value);
            if($updated)
            {
                $phone_number_with_cc = env('SMS_COUNTRY_CODE').$phone_number;

                Log::Info("\n====\n=== SMS content: ".$phone_number_with_cc.'=='.$sms_content);
                
                if(env("SMS_TEST_OTP") == 0)
                {
                    $this->sendSms($phone_number_with_cc,$sms_content);
                }
                $this->success("Verification code has been sent to your phone number. The code will expire in ".env("PHONE_VERIFICATION_OTP_EXPIRE")." minutes.\nStandard text message rates apply.", "");
            }

            

            else
            {
                $this->error("Failed to send verification code. Try again later");
            }
            
        } 
        else
        {
            $this->error("User does not exists");
        }
    
    }

    /* Api function to verify phone of an user (cleaner/client)
    * param: otp (with country code)
    */
    public function validatePhoneVerification(Request $request)
    {
        $input = $request->all();

        $this->validation(
            $request->all(),
            [
                'otp' => 'required',
            ]
        );
        $check_user_exists = Users::where('id', $this->userId)->first();
        /* Check user exists */
        if (!empty($check_user_exists)) {

            $where_array = ['id' => $this->userId, 'phone_verification_code' => $input['otp']];
            $check_user = Users::where($where_array)->first();
            if($check_user)
            {
                $phone_otp_expire = $check_user['phone_otp_expire'];
                if($phone_otp_expire >= date("Y-m-d H:i:s") )
                {
                    $update_value = ['phone_verification_code'=>NULL,'phone_otp_expire'=>NULL,'is_phone_number_verified' => 1];
                    $updated = DB::table('users')->where('id', $check_user_exists['id'])->update($update_value);
                    $this->success("Phone verification successfull", "");
                    
                }
                else
                {                    
                    $this->error("Phone verification code expired.");  
                }
                
            }
            else
            {
                $this->error("Invalid verification code.");  
            }
            
        } 
        else
        {
            $this->error("User does not exists");
        }
    }
   
}
