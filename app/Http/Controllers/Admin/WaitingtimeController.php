<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Bookings;
use App\Models\admin\Waiting;

use App\Plan;
use Illuminate\Http\Request;

class WaitingtimeController extends Controller
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
        $waiting_time = Waiting::all(); 
        return view('admin.waitingtime.index', compact('waiting_time'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.waitingtime.create');
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
        Waiting::create($requestData);
        return redirect('admin/waitingtime')->with('flash_message', 'Waiting Time added!');
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
        $waiting_time = Waiting::findOrFail($id);
        return view('admin.waitingtime.show', compact('waiting_time'));
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
        $waiting_time = Waiting::findOrFail($id);
        return view('admin.waitingtime.edit', compact('waiting_time'));
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
        
		$waiting_time = Waiting::findOrFail($id);
        $requestData = $request->all();  
        $waiting_time->update($requestData);
        return redirect('admin/waitingtime')->with('flash_message', 'Waiting Time updated!');
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
        Waiting::destroy($id);
        return redirect('admin/waitingtime')->with('flash_message', 'Waiting Time deleted!');
    }
}

