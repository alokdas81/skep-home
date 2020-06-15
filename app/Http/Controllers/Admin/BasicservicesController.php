<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\api\v1\Users;
use App\Models\admin\Extraservices;
use App\Models\admin\Basicservices;
use Illuminate\Http\Request;
use Auth;
use File;

class BasicservicesController extends Controller
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

        $basicservices = Basicservices::all();
        return view('admin.basicservices.index', compact('basicservices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.basicservices.create');
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

        $insert_data = Basicservices::create($requestData);
        return redirect('admin/basicservices')->with('flash_message', 'Basic Service Added!');
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
        $basicservices = Basicservices::findOrFail($id);
        return view('admin.basicservices.show', compact('basicservices'));
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
        $basicservice = Basicservices::findOrFail($id);
        return view('admin.basicservices.edit', compact('basicservice'));
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
        $basicservices = Basicservices::findOrFail($id);
        $array = ['name' => $requestData['service_name'], 'time' => $requestData['time'], 'price' => $requestData['price']];
        $basicservices->update($array);
        return redirect('admin/basicservices')->with('flash_message', 'Basic Service updated!');
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
        $basicservices = Basicservices::findOrFail($id);
        Basicservices::destroy($id);
        return redirect('admin/basicservices')->with('flash_message', 'Basic Service deleted!');
    }
    
    public function get_user_image_path($image_name, $user_type){
        if(!empty($image_name)){
            $image_path = url('/').'/public/images/extra_services/'.$image_name;
        } else{
            $image_path = '';
        } 
        return $image_path;
    }
}
?>