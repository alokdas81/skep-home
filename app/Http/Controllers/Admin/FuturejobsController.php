<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Users;
use App\Models\api\v1\Bookings;
use App\Models\api\v1\Myspace;
use App\Models\admin\Extraservices;
use Illuminate\Http\Request;
use Auth;
use File;
use DB;

class FuturejobsController extends Controller
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
        $date = date("Y-m-d H:i:s");
        $bookings = DB::select("SELECT bookings.*, users1.first_name as homeowner_name, users2.first_name as cleaner_name 
        FROM `bookings` INNER JOIN users users1 
        ON bookings.user_id = users1.id 
        LEFT JOIN users users2 ON bookings.service_provider_id = users2.id WHERE 
        service_start_time > '".$date."'                  
        ORDER BY created_at DESC");
        return view('admin.futurejobs.index', compact('bookings','request'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.futurejobs.create');
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

        /*$password = password_hash($requestData['password'],PASSWORD_DEFAULT);
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
            $destinationPath = public_path('/images/users/cleaners');
            $image->move($destinationPath, $input['imagename']);
        }
        $requestData['profile_pic'] = $input['imagename'];
        $insert_data = Users::create($requestData);*/

        return redirect('admin/futurejobs')->with('flash_message', 'User Added!');
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
        return view('admin.futurejobs.show', compact('user'));
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
        $booking = Bookings::findOrFail($id);
        $users = DB::select("SELECT * FROM users WHERE user_type = 'homeOwner' AND status = 1 AND authenticate_status = 1");
        $cleaner = DB::select("SELECT * FROM users WHERE user_type = 'cleaner' AND status = 1 AND authenticate_status = 1 AND work_status = 1");
        $extra_services = Extraservices::all();
        if(!empty($booking->booking_services)){
            $values = explode(',',$booking->booking_services);
        } else{
            $values = [];
        }
        $all_services = [];
        foreach($extra_services as $services){
            $all_services[] = $services->name;
        }
        $myspace_details = Myspace::all();
        return view('admin.futurejobs.edit', compact('booking', 'users', 'cleaner', 'extra_services', 'all_services', 'values', 'myspace_details'));
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
        $bookings = Bookings::findOrFail($id);
        $requestData = $request->all();
        if(!empty($requestData['extra_services'])){
            $extra_services = implode(',',$requestData['extra_services']);
            $update_array = ['booking_price' => $this->amountToFloat($requestData['booking_price']), 'user_id' => $requestData['user_id'], 'service_provider_id' => $requestData['service_provider_id'], 'booking_services' => $extra_services, 'space_id' => $requestData['space_id'], 'special_instructions' => $requestData['special_instructions']];
        } else{
            $update_array = ['booking_price' => $this->amountToFloat($requestData['booking_price']), 'user_id' => $requestData['user_id'], 'service_provider_id' => $requestData['service_provider_id'], 'space_id' => $requestData['space_id'], 'special_instructions' => $requestData['special_instructions']];
        }
        $bookings->update($update_array);
        return redirect('admin/futurejobs')->with('flash_message', 'Booking updated!');
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
        Bookings::destroy($id);
        return redirect('admin/futurejobs')->with('flash_message', 'Booking deleted!');
    }
}

