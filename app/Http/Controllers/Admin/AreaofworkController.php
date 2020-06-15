<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Bookings;
use App\Models\admin\Regions;

use App\Plan;
use DB;
use Illuminate\Http\Request;

class AreaofworkController extends Controller
{
	
	public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request){
        $regions = DB::select("SELECT id, region_id, region_name, region_lat_lng FROM regions");
        $regions_val = [];
        foreach($regions as $region){
            $regions_val[] = $region;
        }
        return view('admin.areaofwork.index', compact('regions'));
    }

    public function mapRegions(Request $request){
        $regions = DB::select("SELECT region_lat_lng FROM regions");
        $regions_val = [];
        foreach($regions as $region){
            $regions_val[] = $region->region_lat_lng;
        }
        return view('admin.areaofwork.display',compact('regions'));
    }

    public function saveMapRegions(Request $request){

    	$latlongs = $request->input('latlongs');
        $center_position = $request->input('center_positions');
        $region_name = (!empty($request->input('region_name')))?$request->input('region_name'):'';
    	$random_id = str_random(6);
    	$region_code = 'REG_'.$random_id;
    	$array = [
    		'region_id' => $region_code,
            'region_name' => $region_name,
    		'region_lat_lng' => json_encode($latlongs),
            'center_positions' => json_encode($center_position),
    		'status' => '1'
    	];
    	$regions = Regions::create($array);
    	$id = $regions['id'];
    	echo $id;
        die;
    }

    public function deleteMapRegion(Request $request){
        $id = $request->input('id');
        $delete = DB::table('regions')->where('id', $id)->delete();
        echo $delete;
        die;
    }
}

?>