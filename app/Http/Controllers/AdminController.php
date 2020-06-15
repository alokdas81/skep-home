<?php



namespace App\Http\Controllers;

use App\Models\api\v1\Users;
use App\Models\api\v1\Tickets;
use Illuminate\Http\Request;

use DB;

class AdminController extends Controller

{

    /**

     * Create a new controller instance.

     *

     * @return void

     */

    public function __construct()

    {

        $this->middleware('auth:admin');

    }

    /**

     * Show the application dashboard.

     *

     * @return \Illuminate\Http\Response

     */

    public function index()
    {
        $date = date("Y-m-d");
        $start_date_of_month = date("Y-m-01",strtotime($date));
        $end_date_of_month = date("Y-m-t",strtotime($date));
        $cleaners = Users::where('user_type','cleaner')->get();
        $cleaner_count = count($cleaners);
        $homeOwners = Users::where('user_type','homeOwner')->get();
        $homeowner = count($homeOwners);
        $tickets = DB::select("SELECT * FROM tickets WHERE status = 1 AND date(created_at) >= '".$start_date_of_month."' AND date(created_at) <= '".$end_date_of_month."'");
        $tickets_count = count($tickets);
        $open_tickets = Tickets::where('status', '1')->get();
        $open_tickets_count = count($open_tickets);
        $approved_cleaner = DB::select("SELECT * FROM users WHERE work_status = '1' AND authenticate_status = '1' AND status = '1' AND user_type = 'cleaner'");
        $approved_cleaner_count = count($approved_cleaner);
        $unapproved_cleaners = DB::select("SELECT * FROM users WHERE work_status = '1' AND authenticate_status = '0' AND status = '1' AND user_type = 'cleaner'");
        $unapproved_cleaners_count = count($unapproved_cleaners);
        $approved_homeowner = DB::select("SELECT * FROM users WHERE authenticate_status = '1' AND status = '1' AND user_type = 'homeOwner'");
        $approved_homeowner_count = count($approved_homeowner);
        $unapproved_homeowners = DB::select("SELECT * FROM users WHERE authenticate_status = '0' AND status = '1' AND user_type = 'homeOwner'");
        $unapproved_homeowners_count = count($unapproved_homeowners);
        return view('admin.dashboard.index',compact('cleaner_count', 'homeowner', 'tickets_count', 'open_tickets_count', 'approved_cleaner_count', 'unapproved_cleaners_count', 'approved_homeowner_count', 'unapproved_homeowners_count'));
    }
}