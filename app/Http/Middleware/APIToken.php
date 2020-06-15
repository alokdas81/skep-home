<?php

namespace App\Http\Middleware;

use App;
use Closure;
use DB;
use Session;

class APIToken
{

    public function handle($request, Closure $next)
    {
		
		$access_type = $request->header('accessType');
		$user_type = $request->header('userType');

        if ($request->header('AuthorizationToken') && $request->header('userId')) {
            $userId = $request->header('userId');
            $user_type = $request->header('userType');
            $accesstoken = $request->header('AuthorizationToken');
            $token = DB::table('tokens')->where(['user_id' => $userId, 'user_type' => $user_type, 'token_status' => 1])->first();
           // echo $accesstoken.'<>'.$token->access_token;
            //exit;
            
            $response = [];
            if (!empty($token)) {
                if ($token->access_token == $accesstoken) {
                    return $next($request);
                } else {
                    $response['code'] = 403;
                    $response['success'] = false;
                    $response['message'] = "Sorry, you cannot login to multiple devices.";
                    $response['data'] = '';
                    return response()->json($response, 403);
                }
            } else {
                $response['code'] = 401;
                $response['success'] = false;
                $response['message'] = 'Unauthorised';
                $response['data'] = '';
                return response()->json($response, 401);
            }
		} else if ($user_type == 'owner' && !$request->header('AuthorizationToken') && !$request->header('userId')) {
			return $next($request);
		} else {
            $response['code'] = 401;
            $response['success'] = false;
            $response['message'] = 'Not a valid API request.';
            $response['data'] = '';
            return response()->json($response, 401);
        }
    }

}
