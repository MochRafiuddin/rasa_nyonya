<?php
namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Models\MApiKey;
use App\Models\ApiLogs;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MyAuth
{

    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->header('auth-key');
        $actions = $request->route();
        $cek_token = MApiKey::where("token",$token)->first();

        if(!$cek_token || !$token) {
            // if(isset($actions[1]['authOptional']) && $actions[1]['authOptional']){
            //     return $next($request);
            // }
            // Unauthorized response if token not there
            return response()->json([
                "message" => "Access denied: This is private service.",
                "status" => 401,
            ],401);
        }
        
        if ($cek_token) {
            $user = User::where('id_user',$cek_token->id_user)->first();
            Auth::login($user); 
        }        

        return $next($request);
        
    }

    public function terminate($request, $response)
    {
        $uri=$request->fullUrl();
        $method = $request->method();
        $header = $request->header();
        $api_key = $request->header('auth-key');
        $param_input= $request->all();
        $param = json_encode(['header' => $header, 'param' => $param_input]);
        $respon_code = http_response_code();
        $response_data = json_encode($response ? $response->getData() : []) ;
        $user = MApiKey::where("token",$api_key)->first();

        ApiLogs::create([
            'uri' => $uri,
            'method' => $method,
            'param' => $param,
            'api_key' => $api_key,
            'response_data' => $response_data,
            'response_code' => $respon_code,
        ]);
    }
}