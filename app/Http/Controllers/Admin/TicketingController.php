<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use config;
use Illuminate\Http\Request;
use Illuminate\Http\SimpleMesaage;
use Illuminate\Support\Facades\Hash;

use App\Models\api\v1\Token;
use App\Models\api\v1\Users;
use App\Models\api\v1\Tickets;

use Mail;
use Input;
use Auth;
use DB;

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
            $unique_number = str_random(6);
            $ticket_number = 'SKEP_'.$unique_number;
            $description = (!empty($input['description']))?$input['description']:'';
            $values = [
                'ticket_number' => $ticket_number,
                'user_id' => $this->userId,
                'user_type' => $this->userType,
                'title' => $input['title'],
                'description' => $description,
                'status' => 1 
            ];
            $createTicket = Tickets::create($values);
            $id = $createTicket['id'];
            if(!empty($id)){
                $this->success("Ticket Created","");
            } else{
                $this->error("Something went Wrong");
            }
        } else{
            $this->error("User not exists");
        }
    }

}

?>