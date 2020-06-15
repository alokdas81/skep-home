<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Bookings;
use App\Models\api\v1\Users;
use App\Models\api\v1\Myspace;
use App\Models\admin\Extraservices;
use DB;
use App\Plan;
use Illuminate\Http\Request;

class BookingsinstantController extends Controller
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
        $keyword = $request->get('search');

        $bookings = DB::select("SELECT bookings.*, users1.first_name as homeowner_name, users2.first_name as cleaner_name 
        FROM `bookings` INNER JOIN users users1 ON 
        bookings.user_id = users1.id 
        LEFT JOIN users users2 ON bookings.service_provider_id = users2.id WHERE 
        bookings.booking_type = 'instant'          
        ORDER BY created_at DESC");
        return view('admin.bookingsinstant.index', compact('bookings','request'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $users = DB::select("SELECT * FROM users WHERE user_type = 'homeOwner' AND status = 1 AND authenticate_status = 1");
        $cleaner = DB::select("SELECT * FROM users WHERE user_type = 'cleaner' AND status = 1 AND authenticate_status = 1 AND work_status = 1");
        $extra_services = Extraservices::all();
        $all_services = [];
        foreach($extra_services as $services){
            $all_services[] = $services->name;
        }
        $myspace_details = Myspace::all();
        return view('admin.bookingsinstant.create',compact('users', 'cleaner', 'extra_services', 'all_services', 'myspace_details'));
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
        $booking_date = date("Y-m-d", strtotime($requestData['booking_date']));
        if(!empty($requestData['extra_services'])){
            $extra_services = implode(',',$requestData['extra_services']);
            $create_array = ['booking_date' => $booking_date, 'service_start_time' => $requestData['service_start_time'], 'service_end_time' => $requestData['service_end_time'], 'booking_price' => $this->amountToFloat($requestData['booking_price']), 'user_id' => $requestData['user_id'], 'service_provider_id' => $requestData['service_provider_id'], 'booking_services' => $extra_services, 'space_id' => $requestData['space_id'], 'special_instructions' => $requestData['special_instructions'], 'booking_type' => 'instant'];
        } else{
            $create_array = ['booking_date' => $booking_date, 'service_start_time' => $requestData['service_start_time'], 'service_end_time' => $requestData['service_end_time'], 'booking_price' => $this->amountToFloat($requestData['booking_price']), 'user_id' => $requestData['user_id'], 'service_provider_id' => $requestData['service_provider_id'], 'space_id' => $requestData['space_id'], 'special_instructions' => $requestData['special_instructions'], 'booking_type' => 'instant'];
        }
        Bookings::create($create_array);
        return redirect('admin/bookingsinstant')->with('flash_message', 'Instant Booking created!');
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
        $bookings = Bookings::findOrFail($id);
        return view('admin.bookingsinstant.show', compact('bookings'));
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
        return view('admin.bookingsinstant.edit', compact('booking', 'users', 'cleaner', 'extra_services', 'all_services', 'values', 'myspace_details'));
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
        return redirect('admin/bookingsinstant')->with('flash_message', 'Instant Booking updated!');
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
        return redirect('admin/bookingsinstant')->with('flash_message', 'Instant Booking deleted!');
    }
}
