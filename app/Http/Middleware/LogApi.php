<?php
namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Models\ApiLogs;
use Auth;

class LogApi
{

    public function handle($request, Closure $next, $guard = null)
    {
        return $next($request);      
    }

    public function terminate($request, $response)
    {
        $uri=$request->fullUrl();
        $method = $request->method();
        $header = $request->header();
        $api_key = $request->header('Authorization');
        $param_input= $request->all();
        $param = json_encode(['header' => $header, 'param' => $param_input]);
        $respon_code = http_response_code();
        $response_data = json_encode($response ? $response->getData() : []) ;        
        $created_by = "";

        ApiLogs::create([
            'uri' => $uri,
            'method' => $method,
            'param' => $param,
            'api_key' => $api_key,
            'response_data' => $response_data,
            'response_code' => $respon_code,
            'created_by' => $created_by,
        ]);
    }
}