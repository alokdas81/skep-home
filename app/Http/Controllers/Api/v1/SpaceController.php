<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;

use App\Models\Token;
use App\Models\api\v1\Users;
use App\Models\api\v1\Myspace;
use App\Models\admin\Extraservices;
use App\Models\api\v1\Bookings;
use Config;
use Illuminate\Http\Request;
use Illumintae\Http\SimpleMessage;
use Illuminate\Support\Facades\Hash;
use Input;
use Auth;
use DB;

class SpaceController extends Controller
{
	public $successStatus = 200;
    public $unauthorizedStatus = 401;
    public $user_type = "";
    private $apiToken;

    public function __construct(Request $request){
        // Unique Token
        $this->apiToken = uniqid(base64_encode(str_random(20)));
        $this->user_type = $request->header('userType') ? $request->header('userType') : "";
        $this->userId = $request->header('userId') ? $request->header('userId') : "";
    }

    public function saveMySpace(Request $request){
    	$input = $request->all();

    	$this->validation(
    		$request->all(),
    		[
    			'address' => 'required',
    			'latitude' => 'required',
    			'longitude' => 'required',
    			'unit_number' => 'required_unless:type,House',
    			'city' => 'required',
    			'postalcode' => 'required',
    			'type' => 'required',
    			'bedrooms' => 'required',
    			'bathrooms' => 'required',
    			//'dens' => 'required'
    		]
    	);

    	$check_user_exists = Users::where('id',$this->userId)->first();
    	if(!empty($check_user_exists)){
    		$input['user_id'] = $this->userId;
            $check_already_default_exists = Myspace::where(['user_id' => $input['user_id'], 'set_as_default' => 1])->first();
            if(!empty($check_already_default_exists)){
                if(!empty($input['set_as_default'])){
                    if($input['set_as_default'] == 1){
                        $check_already_default_exists->set_as_default = 0;
                        $check_already_default_exists->save();
                    }
                }
                $save_space = Myspace::create($input);
                $space_id = $save_space['id'];
                if(!empty($space_id)){
                    $this->success('Space Added Successfully',"");
                } else{
                    $this->error('Space Not Added');
                }
            } else{
                $save_space = Myspace::create($input);
                $space_id = $save_space['id'];
                if(!empty($space_id)){
                    $this->success('Space Added Successfully',"");
                } else{
                    $this->error('Space Not Added');
                }
            }
    	} else{
    		$this->error('User Not Found');
    	}
    }

    public function getMySpaces(){
    	$user_id_exists = Users::where('id', $this->userId)->first();

    	if(!empty($user_id_exists)){
    		$get_user_spaces = Myspace::where('user_id',$this->userId)->get();
            $values_here = count($get_user_spaces);
            if($values_here > 0){
                $values = $get_user_spaces;
                $simple_response = [];
                $default_response = [];
                foreach($values as $value){
                    if($value->set_as_default == 1){
                        $default_response[] = [
                            'id' => (string) $value->id,
                            'user_id' => (string) $value->user_id,
                            'name' => (string) $value->name,
                            'address' => (string) $value->address,
                            'latitude' => (string) $value->latitude,
                            'longitude' => (string) $value->longitude,
                            'unit_number' => (string) $value->unit_number,
                            'city' => (string) $value->city,
                            'postalcode' => (string) $value->postalcode,
                            'buzz_number' => (string) $value->buzz_number,
                            'type' => (string) $value->type,
                            'size' => (string) $value->size,
                            'bedrooms' => (string) $value->bedrooms,
                            'bathrooms' => (string) $value->bathrooms,
                            'dens' => (string) $value->dens,
                            'family_room' => (string) $value->family_room,
                            'dining_room' => (string) $value->dining_room,
                            'powder_room' => (string) $value->powder_room,
                            'special_instructions' => (string) $value->special_instructions,
                            'set_as_default' => (string) $value->set_as_default
                        ];
                    } else{
                        $simple_response[] = [
                            'id' => (string) $value->id,
                            'user_id' => (string) $value->user_id,
                            'name' => (string) $value->name,
                            'address' => (string) $value->address,
                            'latitude' => (string) $value->latitude,
                            'longitude' => (string) $value->longitude,
                            'unit_number' => (string) $value->unit_number,
                            'city' => (string) $value->city,
                            'postalcode' => (string) $value->postalcode,
                            'buzz_number' => (string) $value->buzz_number,
                            'type' => (string) $value->type,
                            'size' => (string) $value->size,
                            'bedrooms' => (string) $value->bedrooms,
                            'bathrooms' => (string) $value->bathrooms,
                            'dens' => (string) $value->dens,
                            'family_room' => (string) $value->family_room,
                            'dining_room' => (string) $value->dining_room,
                            'powder_room' => (string) $value->powder_room,
                            'special_instructions' => (string) $value->special_instructions,
                            'set_as_default' => (string) $value->set_as_default
                        ];
                    }
                }
                if(!empty($default_response)){
                    $response = array_merge($default_response,$simple_response);
                } else{
                    $response = $simple_response;
                }
                $this->success("Spaces found", $response);
            } else{
                $this->error("No Space Exist of this user");
            }
    	}
    }

    public function editMySpace(Request $request){
    	$input = $request->all();

    	$this->validation(
    		$request->all(),
    		[
    			'space_id' => 'required'
    		]
    	);

    	$check_user_id = Users::where('id', $this->userId)->first();
    	if(!empty($check_user_id)){
    		$check_space_exist = Myspace::where(['id' => $request->input('space_id'), 'user_id' => $this->userId])->first();
            $check_already_default_exists = Myspace::where(['user_id' => $this->userId, 'set_as_default' => 1])->get();
    		if(!empty($check_space_exist)){
                if(!empty($check_already_default_exists)){
                    foreach($check_already_default_exists as $space){
                       if($input['set_as_default'] == 1){
                            $array = ['set_as_default' => 0];
                            $update_space = Myspace::where('id', $space['id'])->update($array);
                        }
                    }
                }
    			$check_space['name'] = (!empty($request->input('name')))?$request->input('name'):$check_space_exist['name'];
    			$check_space['address'] = (!empty($request->input('address')))?$request->input('address'):@$check_space_exist['address'];
    			$check_space['latitude'] = (!empty($request->input('latitude')))?$request->input('latitude'):@$check_space_exist['latitude'];
    			$check_space['longitude'] = (!empty($request->input('longitude')))?$request->input('longitude'):@$check_space_exist['longitude'];
    			$check_space['unit_number'] = (!empty($request->input('unit_number')))?$request->input('unit_number'):@$check_space_exist['unit_number'];
    			$check_space['city'] = (!empty($request->input('city')))?$request->input('city'):@$check_space_exist['city'];
    			$check_space['postalcode'] = (!empty($request->input('postalcode')))?$request->input('postalcode'):@$check_space_exist['postalcode'];
    			$check_space['buzz_number'] = (!empty($request->input('buzz_number')))?$request->input('buzz_number'):@$check_space_exist['buzz_number'];
    			$check_space['type'] = (!empty($request->input('type')))?$request->input('type'):@$check_space_exist['type'];
    			$check_space['bedrooms'] = (!empty($request->input('bedrooms')))?$request->input('bedrooms'):@$check_space_exist['bedrooms'];
    			$check_space['bathrooms'] = (!empty($request->input('bathrooms')))?$request->input('bathrooms'):@$check_space_exist['bathrooms'];
    			$check_space['dens'] = (!empty($request->input('dens')))?$request->input('dens'):@$check_space_exist['dens'];
                $check_space['family_room'] = (!empty($request->input('family_room')))?$request->input('family_room'):@$check_space_exist['family_room'];
                $check_space['dining_room'] = (!empty($request->input('dining_room')))?$request->input('dining_room'):@$check_space_exist['dining_room'];
                $check_space['powder_room'] = (!empty($request->input('powder_room')))?$request->input('powder_room'):@$check_space_exist['powder_room'];
                $check_space['set_as_default'] = (!empty($request->input('set_as_default')))?$request->input('set_as_default'):@$check_space_exist['set_as_default'];
    			$check_space['special_instructions'] = (!empty($request->input('special_instructions')))?$request->input('special_instructions'):@$check_space_exist['special_instructions'];
                $values = Myspace::where(['id' => $request->input('space_id'), 'user_id' => $this->userId])->update($check_space);

    			if($check_space_exist->save()){
    				$myspace = Myspace::where('id',$request->input('space_id'))->first();

    				$response = [
    					'id' => (string) $myspace->id,
    					'user_id' => (string) $myspace->user_id,
    					'name' => (string) $myspace->name,
    					'address' => (string) $myspace->address,
    					'latitude' => (string) $myspace->latitude,
    					'longitude' => (string) $myspace->longitude,
    					'unit_number' => (string) $myspace->unit_number,
    					'city' => (string) $myspace->city,
    					'postalcode' => (string) $myspace->postalcode,
    					'buzz_number' => (string) $myspace->buzz_number,
    					'type' => (string) $myspace->type,
    					'bedrooms' => (string) $myspace->bedrooms,
    					'bathrooms' => (string) $myspace->bathrooms,
    					'dens' => (string) $myspace->dens,
                        'family_room' => (string) $myspace->family_room,
                        'dining_room' => (string) $myspace->dining_room,
                        'powder_room' => (string) $myspace->powder_room,
                        'set_as_default' => (string) $myspace->set_as_default,
    					'special_instructions' => (string) $myspace->special_instructions
    				];

    				$this->success("Space Edited",$response);
    			} else{
    				$this->error("Space Not Edited");
    			}
    		} else{
    			$this->error("Space Not Exists");
    		}
    	} else{
    		$this->error("User Not Exists");
    	}
    }
}
?>
