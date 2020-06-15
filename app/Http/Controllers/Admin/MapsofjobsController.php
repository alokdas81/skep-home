<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Bookings;
use App\Models\admin\Regions;

use App\Plan;
use DB;
use Illuminate\Http\Request;

class MapsofjobsController extends Controller{
	public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request){
    	$current_date = date("Y-m-d H:i:s");
        $upcomings_bookings = DB::select("SELECT * FROM `bookings` WHERE `service_start_time` >  '".$current_date."' AND `is_cancelled` = 0 AND `is_orphan_booking` = 0 AND `booking_status` = '1'");

        $past_bookings = DB::select("SELECT * FROM `bookings` WHERE `service_start_time` <  '".$current_date."' AND `is_cancelled` = 0 AND `is_orphan_booking` = 0 AND `booking_status` = '1'");
        $upcoming_services = [];
        foreach($upcomings_bookings as $upcoming){
        	$upcoming_services[] = ['latitude'=>$upcoming->latitude,'longitude'=>$upcoming->longitude];
        	
        }
        $past_services = [];
        foreach($past_bookings as $past){
        	$past_services[] = ['latitude'=>$past->latitude,'longitude'=>$past->longitude];
        }


        return view('admin.mapsofjobs.index', compact('upcoming_services', 'past_services'));
    }
}