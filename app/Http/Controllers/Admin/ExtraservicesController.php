<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\api\v1\Users;
use App\Models\admin\Extraservices;
use Illuminate\Http\Request;
use Auth;
use File;

class ExtraservicesController extends Controller
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

        $extraservices = Extraservices::all();
        return view('admin.extraservices.index', compact('extraservices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.extraservices.create');
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
        //print_r($request->file('unselected_image')));die;
        $values_data['name'] = $requestData['service_name'];
        $values_data['time'] = $requestData['time'];
        $values_data['price'] = $requestData['price'];

        if(!empty($request->file('image'))){
            $image = $request->file('image');
            $input['imagename'] = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images/extra_services');
            $image->move($destinationPath, $input['imagename']);
        }
        $values_data['image'] = (!empty($input['imagename']))?$input['imagename']:'';

        if(!empty($request->file('unselected_image'))){
            $image = $request->file('unselected_image');
            $input['unselected_imagename'] = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images/extra_services');
            $image->move($destinationPath, $input['unselected_imagename']);
        }
        $values_data['unselected_image'] = (!empty($input['unselected_imagename']))?$input['unselected_imagename']:'';

        $insert_data = Extraservices::create($values_data);
        return redirect('admin/extraservices')->with('flash_message', 'Service Added!');
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
        $extraservices = Extraservices::findOrFail($id);
        return view('admin.extraservices.show', compact('extraservices'));
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
        $user = Extraservices::findOrFail($id);
        $user_image = $user->image;
        $user_extra_unselectedimage = $user->unselected_image;
        if(!empty($user_image)){
           $user['extra_service_image'] = $this->get_user_image_path($user_image, 'extra_services');
        }
        if(!empty($user_extra_unselectedimage)){
            $user['extra_service_unselectedimage'] = $this->get_user_image_path($user_extra_unselectedimage, 'extra_services');
        }
        return view('admin.extraservices.edit', compact('user'));
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
        $service = Extraservices::findOrFail($id);
        $array = ['name' => $requestData['service_name'], 'time' => $requestData['time'], 'price' => $requestData['price']];
        $service->update($array);
        if(!empty($request->file('image'))){
            $check_image_exists = Extraservices::where('id',$id)->get();
            
            if(!empty($check_image_exists[0]['image'])){
                $image_path = "/images/extra_services/".$check_image_exists[0]['image'];
                if(File::exists(public_path($image_path))) {
                    File::delete(public_path($image_path));
                }
            }
            $image = $request->file('image');
            $input['imagename']= time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images/extra_services');
            $image->move($destinationPath, $input['imagename']);
            $service->update(['image' => $input['imagename']]);
        }

        if(!empty($request->file('unselected_image'))){
            $check_image_exists = Extraservices::where('id',$id)->get();
            
            if(!empty($check_image_exists[0]['unselected_image'])){
                $image_path = "/images/extra_services/".$check_image_exists[0]['unselected_image'];
                if(File::exists(public_path($image_path))) {
                    File::delete(public_path($image_path));
                }
            }
            $image = $request->file('unselected_image');
            $input['unselected_imagename']= time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images/extra_services');
            $image->move($destinationPath, $input['unselected_imagename']);
            $service->update(['unselected_image' => $input['unselected_imagename']]);
        }
        return redirect('admin/extraservices')->with('flash_message', 'Service updated!');
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
        $service = Extraservices::findOrFail($id);
        if(!empty($service)){
            $image = $service->image;
            if(!$image){
                $image_path = "/images/extra_services/".$check_image_exists[0]['image'];
                if(File::exists(public_path($image_path))) {
                    File::delete(public_path($image_path));
                }
            }
        }
        Extraservices::destroy($id);
        return redirect('admin/extraservices')->with('flash_message', 'Service deleted!');
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