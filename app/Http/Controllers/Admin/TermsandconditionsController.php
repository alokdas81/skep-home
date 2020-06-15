<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Users;
use App\Models\admin\TermsConditions;
use Illuminate\Http\Request;
use Auth;
use File;
use DB;

class TermsandconditionsController extends Controller{
	public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /* Get All Tickets List */

    public function index(Request $request){
        $pages = TermsConditions::all(); 
    	return view('admin.termsandconditions.index',compact('pages'));
    }

    public function create()
    {
        return view('admin.termsandconditions.create');
    }

    public function store(Request $request){
        $requestData = $request->all();
        $pages = TermsConditions::create($requestData);
        return redirect('admin/termsandconditions')->with('flash_message','Page data added successfully!');
    }

    public function show($id)
    {
        $pages = TermsConditions::findOrFail($id);
        return view('admin.termsandconditions.show', compact('pages'));
    }

    public function edit($id){
        $pages = TermsConditions::findOrFail($id);
        return view('admin.termsandconditions.edit', compact('pages'));
    }

    public function update(Request $request, $id){
        $requestData = $request->all();
        $pages = TermsConditions::findOrFail($id);
        $array = ['title' => $requestData['title'], 'description' => $requestData['description']];
        $pages->update($array);
        return redirect('admin/termsandconditions')->with('flash_message','Page data updated successfully!');
    }

    public function destroy($id)
    {
        TermsConditions::destroy($id);
        return redirect('admin/termsandconditions')->with('flash_message', 'Page Deleted successfully');
    }
}

?>