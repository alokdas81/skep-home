<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\api\v1\Users;
use App\Models\admin\TermsConditions;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    /**
    * Reset password by confirming email
    *
    * @return response
    */  
   public function resetPassword(Request $request) {

       $user = $request->input('user');
       $usertoken = $request->input('usertoken');

       if(!empty($user) && !empty($usertoken))
       {
           $check_token =  Users::where(['id'=>$user, 'remember_token'=>$usertoken])->first();
           if ($check_token) {
               return view('emails.resetpass', compact('user', 'usertoken'));
           }else{
               return view('emails.expire_link');
           }
       }else{
           return view('emails.alert');
       }
   }


   /**
    * Save Reset password by confirming email
    * */
   public function saveResetPassword(Request $request)
   {
      $request->validate([
        "user" => "required",
        "usertoken" => "required",
      ]);
      $user = $request->input('user');
      $usertoken = $request->input('usertoken');
      $newPassword = password_hash($request->input('password'), PASSWORD_DEFAULT);

      $check_token =  Users::where(['id'=>$user, 'remember_token'=>$usertoken])->first();
      if ($check_token) {
        $update =  Users::where(['id'=>$user, 'remember_token'=>$usertoken])->update(['remember_token'=>'', 'password'=>$newPassword]);
        if($update){
          return view('emails.thankyou', compact('user', 'usertoken'));
        }else{
          Session::flash('message', 'Something went wrong, Please try again!');
          Session::flash('alert-class', 'alert-danger'); 
          return view('emails.resetpass', compact('user', 'usertoken'));
        }
      }else{
        return view('emails.alert');
      }
    }

    public function termsConditions(Request $request){
      $terms_and_conditions = TermsConditions::where('terms','terms_and_conditions')->first();
      return view('help_pages/terms_and_conditions',compact('terms_and_conditions'));
    }

    public function privacyPolicy(Request $request){
      $privacy_policy = TermsConditions::where('terms','privacy_policy')->first();
      return view('help_pages/privacy_policy', compact('privacy_policy'));
    }

    
}
