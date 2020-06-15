<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Users;

use App\User;
use App\Post;
use App\Garage;
use Illuminate\Http\Request;
use Auth;
class DashboardController extends Controller
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
    public function index(Request $request){
        return view('admin.dashboard.index',compact('users'));
    }
    
    public function logout(Request $request){
        Auth::logout();
        redirect('/login');
    }    
}
