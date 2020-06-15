<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use config;
use Illuminate\Http\Request;
use Illuminate\Http\SimpleMesaage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


use App\Models\api\v1\Token;
use App\Models\api\v1\Users;
use App\Models\api\v1\Tickets;

use Mail;
use Input;
use Auth;
use DB;

use Illuminate\Support\Facades\Route;

class TicketingController extends Controller{

	public $successStatus = 200;
    public $unauthorizedStatus = 401;
    public $userType = "";
    private $apiToken;

    public function __construct(Request $request){
                
        // Unique Token
        $this->apiToken = uniqid(base64_encode(str_random(20)));
        $this->userType = $request->header('userType') ? $request->header('userType') : "";
        $this->userId = $request->header('userId') ? $request->header('userId') : "";
    }

    public function createTicket(Request $request){
    	
    	$input = $request->all();
    	$this->validation(
    		$request->all(),
    		[
    			'title' => 'required'
    		]
    	);
    	$where = ['id' => $this->userId, 'account_blocked' => '0'];
    	$check_user_exists = Users::where($where)->first();
    	if(!empty($check_user_exists)){
    	    
    	    $user_email = $check_user_exists['email'];
            $unique_number = str_random(6);
    		$ticket_number = 'SKEP_'.$unique_number;
            $title = (!empty($input['title']))?$input['title']:'';
            $description = (!empty($input['description']))?$input['description']:'';
    		$job_id = (!empty($input['job_id']))?$input['job_id']:'';
    		$values = [
    			'ticket_number' => $ticket_number,
    			'user_id' => $this->userId,
    			'user_type' => $this->userType,
    			'title' => $title,
    			'description' => $description,
    			'job_id' =>$job_id,
    			'status' => 0
    		];
    		$createTicket = Tickets::create($values);
    		$id = $createTicket['id'];
    		if(!empty($id)) {
    			
    			Log::Info("Ticket Created=============");
    			$this->sendTicketMail($ticket_number,$user_email,$job_id,$title,$description);    
    			
    			
    			
    		} else{
    			$this->error("Something went wrong! Please try again.");
    		}
    	} else{
    		$this->error("User not exists");
    	}
    }
    public function sendTicketMail($ticket_number,$user_email,$job_id,$title,$description) {
        Log::Info("Ticket about to send the email=============");
        
        $subject = "[TICKET NUMBER: ".$ticket_number."] We have received your support request";
        $content = "Thank You! You will be hearing from us soon.";
        if($title !='')
        {
            $content .= "\nTitle: ".$title."";
        }
        if($description !='')
        {
            $content .= "\nDescription: ".$description."";
        }
        $content .= "\n\nTeam Skep";
        
        if(!empty($job_id))
        {
            $headers = "From: Team Skep <".env("MAIL_TICKET_SUPPORT").">\r\n";
        }
        else
        {
            $headers = "From: Team Skep <".env("MAIL_SUPPORT").">\r\n";
        }
        if(env("MAIL_TICKET_SUPPORT_CC")!="")
        {
            $headers .= "BCC: ".env("MAIL_TICKET_SUPPORT_CC")."\r\n";
        }
        
        
    	if(mail($user_email,$subject,$content,$headers)){
          //  echo 'Ticket Email Sent Successfully';
           $this->success("Ticket Created","");
            
        } else{
            //echo 'Something Went Wrong';
            $this->error("Something went wrong!.");
        }
    }
}

?>