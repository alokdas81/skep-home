<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Users;
use App\Models\api\v1\Ratings;
use Illuminate\Http\Request;
use Auth;
use File;
use DB;
use Mail;
use Illuminate\Support\Facades\Log;
class CleanersController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth:admin');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Users::where('user_type','cleaner')->get();
        return view('admin.cleaners.index', compact('user'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.cleaners.create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $requestData = $request->all();
        $password = password_hash($requestData['password'],PASSWORD_DEFAULT);
        $your_date = date("Y/m/d", strtotime($requestData['date_of_birth']));
        $get_address_lats = $this->get_address_lat_long($request['address']);
        $requestData['latitude'] = $get_address_lats['latitude'];
        $requestData['longitude'] = $get_address_lats['longitude'];
        $requestData['date_of_birth'] = $your_date;
        $requestData['password'] = $password;
        $requestData['user_type'] = 'cleaners';
        if(!empty($request->file('profile_pic'))){
            $image = $request->file('profile_pic');
            $input['imagename']= time().'.'.$image->getClientOriginalExtension();
            $image_name = $input['imagename'];
            $destinationPath = public_path('/images/users/cleaners');
            $image->move($destinationPath, $input['imagename']);
        } else{
            $image_name = '';
        }
        $requestData['profile_pic'] = $input['imagename'];
        $password = password_hash($requestData['password'],PASSWORD_DEFAULT);
        $array = ['password'=>$password, 'first_name' => $requestData['first_name'], 'last_name' => $requestData['last_name'], 'phone_number' => $requestData['phone_number'], 'date_of_birth' => $your_date,'gender' => $requestData['gender'],'address'=> $requestData['address'], 'latitude' => $latitude, 'longitude' => $longitude, 'profile_pic' => $image_name];
        $insert_data = Users::create($array);
        $values = ['ratings_by' => '1', 'ratings_for' => $insert_data['id'], 'ratings' => $requestData['ratings']];
        $insert_ratings = Ratings::create($values);
        return redirect('admin/cleaners')->with('flash_message', 'User Added!');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = Users::findOrFail($id);
        $area_of_work = $user['work_area'];
        if(!empty($area_of_work)){
            $work_area = explode(',',$area_of_work);
            $work_areas = [];
            foreach($work_area as $area){
                $result = DB::select("SELECT * FROM regions WHERE id = '".$area."'");
                $work_areas[] = $result[0]->region_name;
            }
            if(!empty($work_areas)){
                $areas = implode(', ',$work_areas);
            } else{
                $areas = '';
            }
        }
        
        return view('admin.cleaners.show', compact('user','areas'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $user = Users::findOrFail($id);
        $check_ratings = DB::select("SELECT * FROM ratings WHERE ratings_by = '1' AND ratings_for='".$id."'");
        if(!empty($check_ratings)){
            $ratings = $check_ratings[0]->ratings;
        } else{
            $ratings = '';
        }
        return view('admin.cleaners.edit', compact('user', 'ratings'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->all();
        $your_date = date("Y/m/d", strtotime($requestData['date_of_birth']));
        $user = Users::findOrFail($id);
        $get_address_lats = $this->get_address_lat_long($request['address']);
        $latitude = $get_address_lats['latitude'];
        $longitude = $get_address_lats['longitude'];

        if(!empty($requestData['password'])){
            $password = password_hash($requestData['password'],PASSWORD_DEFAULT);
            if(!empty($request['address'])){
                $array = ['password'=>$password, 'first_name' => $requestData['first_name'], 'last_name' => $requestData['last_name'], 'phone_number' => $requestData['phone_number'], 'date_of_birth' => $your_date,'gender' => $requestData['gender'],'address'=> $requestData['address'], 'latitude' => $latitude, 'longitude' => $longitude];
            } else{
                $array = ['password'=>$password, 'first_name' => $requestData['first_name'], 'last_name' => $requestData['last_name'], 'gender' => $requestData['gender'],'phone_number' => $requestData['phone_number'], 'date_of_birth' => $your_date];
            }

            $user->update($array);

        } else{

            if(!empty($request['address'])){
                $array = ['first_name' => $requestData['first_name'], 'last_name' => $requestData['last_name'], 'phone_number' => $requestData['phone_number'], 'date_of_birth' => $your_date,'address'=> $requestData['address'], 'latitude' => $latitude, 'longitude' => $longitude, 'gender' => $requestData['gender']];
            } else{
                $array = ['first_name' => $requestData['first_name'], 'last_name' => $requestData['last_name'], 'phone_number' => $requestData['phone_number'], 'date_of_birth' => $your_date, 'gender' => $requestData['gender']];
            }
            $user->update($array);
        }

        if(!empty($request->file('profile_pic'))){
            $check_image_exists = Users::where('id',$id)->get();
            if(!empty($check_image_exists[0]['profile_pic'])){
                $image_path = "/images/users/cleaners/".$check_image_exists[0]['profile_pic'];
                if(File::exists(public_path($image_path))) {
                    File::delete(public_path($image_path));
                }
            }
            $image = $request->file('profile_pic');
            $input['imagename']= time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images/users/cleaners');
            $image->move($destinationPath, $input['imagename']);
            $user->update(['profile_pic' => $input['imagename']]);
        }
        $values = DB::select("SELECT * FROM ratings WHERE ratings_by = '1' AND ratings_for = '".$id."'");
        if(!empty($values)){
            $update_vals = ['ratings' => $requestData['ratings']];
            $update_val = DB::table('ratings')->where('id',$values[0]->id)->update($update_vals);
        } else{
            $array = ['ratings_by' => '1', 'ratings_for' => $id, 'ratings' => $requestData['ratings']];
            $insert_ratings = Ratings::create($array);
        }
        return redirect('admin/cleaners')->with('flash_message', 'User updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        Users::destroy($id);
        return redirect('admin/cleaners')->with('flash_message', 'User deleted!');
    }


    public function suspendCleaner(Request $request){
        $id = $request->input('id');
        $check_user_activate = Users::where('id',$id)->first();
        $current_date = date("Y-m-d H:i:s");
        $blocked_date = date("Y-m-d H:i:s",strtotime("+90 days", strtotime($current_date)));
        $update = Users::where('id', $id)->update(['account_blocked' => '1', 'account_blocked_start_date' => $current_date, 'account_blocked_end_date' => $blocked_date, 'status' => 0]);
        echo $update;
    }

    public function approveCleaner(Request $request){
        $id = $request->input('id');
        $check_user_activate = Users::where('id',$id)->first();
        $current_date = date("Y-m-d H:i:s");
        $blocked_date = date("Y-m-d H:i:s",strtotime("+90 days", strtotime($current_date)));
        $update = DB::statement("Update users set account_blocked = 0, account_blocked_start_date = '1970-01-01 00:00:00', account_blocked_end_date = '1970-01-01 00:00:00', status = 1 where id =".$id);

        Log::Info("approveHomeowner: ".json_encode($check_user_activate));         
         $send = Mail::send('emails.account_approve_cleaner', ['user' => $check_user_activate], function ($m) use ($check_user_activate) {
             $m->from(env("MAIL_SUPPORT"), env("MAIL_FROM"));
             $m->bcc(env("MAIL_SUPPORT"), env("MAIL_FROM"));            
             $m->to($check_user_activate['email'], $check_user_activate['first_name'])
             ->subject('Skep Home: Account Activated!');
           });
           
        echo $update;
    }

    public function removeSuperCleanerStatus(Request $request){
        $id = $request->input('id');
        $update = Users::where('id', $id)->update(['is_super_cleaner' => '0']);
        echo $update;
    }

    public function get_address_lat_long($address = ''){
        
        $google_api_key = env("GOOGLE_MAP_API_KEY");
        $data = ["latitude"=>"","longitude"=>""];
        if(!empty($address)){
            $formattedAddr = str_replace(' ','+',$address);
            $geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?key='.$google_api_key.'&address='.$formattedAddr.'&sensor=false');
            $output = json_decode($geocodeFromAddr,true);
            $status = $output['status'];
            if($status == 'OK')
            {
                $location = $output['results'][0]['geometry']['location'];
                $data['latitude']  = $location['lat'];
                $data['longitude'] = $location['lng'];
            
            }
            
            
        }
        return $data;
    }
}
