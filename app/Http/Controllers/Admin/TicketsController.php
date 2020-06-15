<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\api\v1\Users;
use App\Models\api\v1\Tickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Auth;
use File;
use DB;

class TicketsController extends Controller{
	public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /* Get All Tickets List */
    public function index(Request $request){
    	$tickets = DB::select("SELECT tickets.*, users.email, users.user_type FROM tickets LEFT JOIN users ON (tickets.user_id = users.id) ORDER by tickets.id DESC ");
    	return view('admin.tickets.index', compact('tickets'));
    }

    /* Open Ticket Edit Form Here */
    public function edit($id){
        $tickets = DB::select("SELECT tickets.*, users.email, users.user_type FROM tickets LEFT JOIN users ON (tickets.user_id = users.id) WHERE tickets.id=".$id);
        return view('admin.tickets.edit', compact('tickets'));
    }

    /* Update Ticket Status Value From Here*/
    public function update(Request $request, $id){
        $input = $request->all();
        $ticket_exists = Tickets::where('id',$id)->first();
        if(!empty($ticket_exists)){
            $array = ['title' => $input['title'], 'description' => $input['description']];
            $update_ticket = $ticket_exists->update($array);
        }
        return redirect('admin/tickets')->with('flash_message', 'Ticket updated!');
    }

    /* Display a single Ticket Values */
    public function show($id){
    	$tickets = DB::select("SELECT tickets.*, users.email, users.user_type FROM tickets LEFT JOIN users ON (tickets.user_id = users.id) WHERE tickets.id=".$id);
    	
        return view('admin.tickets.show', compact('tickets'));
    }

    /* Delete a single ticket */
    public function destroy($id){
        Tickets::destroy($id);
        return redirect('admin/tickets')->with('flash_message', 'Ticket deleted!');
    }

    /* Close Ticket Here */
    public function closeTicketStatus(Request $request){
    	$id = $request->input('id');
        $update = Tickets::where('id', $id)->update(['status' => '0']);
        echo $update;
    }

    /* Open Tick Here */
    public function openTicketStatus(Request $request){
    	$id = $request->input('id');
        $update = Tickets::where('id', $id)->update(['status' => '1']);
        echo $update;
    }

    /* Send Ticket Question Reply To User From Here */
    public function sendResponseMail(Request $request){
    	$title = $request->input('title');
    	$description = $request->input('description');
    	$email = $request->input('email');
    	$job_id = $request->input('job_id');
    	$user_id = $request->input('user_id');
    	
    	$where = ['id' => $user_id, 'account_blocked' => '0'];
    	$check_user_exists = Users::where($where)->first();
    	
    	$user_name = $check_user_exists['first_name']." ".$check_user_exists['last_name'];
    	
    	 if(!empty($job_id))
        {
            $headers = "From: Team Skep <".env("MAIL_TICKET_SUPPORT").">";
        }
        else
        {
            $headers = "From: Team Skep <".env("MAIL_SUPPORT").">";
        }
        //echo $headers;
        //exit;
        $email_body = "";
        $email_body .= "Hi ".$user_name.",\n";
        if(!empty($job_id))
        {
            $email_body .= "Your Job ID : ".$job_id;
        }
        
        $email_body .= "\n ".$description;
        $email_body .= "\nTeam Skep";
        
        
    	if(mail($email,$title,$email_body,$headers)){
            echo 'Response Successfully Sent';
        } else{
            echo 'Something Went Wrong';
        }
    }
}

?>