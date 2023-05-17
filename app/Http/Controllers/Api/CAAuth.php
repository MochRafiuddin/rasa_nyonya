<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MApiKey;
use App\Models\TOrder;
use App\Traits\Helper;
use Auth;
use Hash;
class CAAuth extends Controller
{
    use Helper;

    public function logout(Request $request)
    {        
        $cek_token = MApiKey::where('token',$request->header('auth-key'))->first();        

        MApiKey::where('token',$request->header('auth-key'))->delete();   
        Auth::logout();
        return response()->json([
            'success' => true,            
            'message' => 'Logout Success',            
            'code' => 1,
        ]);
    }

    public function ubah_password(Request $request)
    {        
        $password_lama = $request->password_lama;
        $password_baru = $request->password_baru;

        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $data = User::where('id_user',$token->id_user)->first();
        $cek = Hash::check($password_lama, $data->password);

        if ($cek == false) {
            return response()->json([
                'success' => false,
                'message' => 'Your old password is wrong, please enter the correct old password',
                'code' => 0,
            ], 400);
        }

        $user = User::find($token->id_user);
        $user->password = Hash::make($password_baru);
        $user->update();

        return response()->json([
            'success' => true,
            'message' => 'Password Update',
            'code' => 1,
        ]);
    }

    public function detail_profil(Request $request)
    {        
        $token = MApiKey::where('token',$request->header('auth-key'))->first();        
        
        $user= User::select('m_user.*','m_courier.id_courier','m_courier.nama','m_courier.alamat','m_courier.no_hp','m_courier.id_area','m_courier.id_wilayah','m_area.nama_area','m_wilayah.nama_wilayah')
                ->join('m_courier','m_courier.id_courier','m_user.id_ref')
				->leftJoin('m_area','m_courier.id_area','m_area.id_area')
				->leftJoin('m_wilayah','m_courier.id_wilayah','m_wilayah.id_wilayah')
                ->where('m_user.id_user',auth::user()->id_user)
                ->where('m_user.tipe_user',2)
                ->get()->toArray();

        // $arr = [1,2,3];
        // $obj = json_decode(json_encode($arr));        
        $singleArrayForCategory = array_reduce($user, 'array_merge', array());
        $data = json_decode(json_encode($singleArrayForCategory));
        // dd($obj);


        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data
        ]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt(['username' => $request->username, 'password' => $request->password, 'deleted' => 1]))
        {
            return response()->json([
                'success' => false,
                'message' => "Oops, we couldn't find your account",
                'code' => 0,
            ], 400);
        }        

        $cek_token = MApiKey::where('id_user',auth::user()->id_user)->first();

        if ($cek_token) {
            MApiKey::where('id_user',auth::user()->id_user)->delete();    
        }
            $key = Helper::generateRandomString();
            $token = new MApiKey();
            $token->id_user = auth::user()->id_user;
            $token->token = $key;
            $token->save();            

            $get_user= User::select('m_user.*','m_courier.id_courier','m_courier.nama','m_courier.alamat','m_courier.no_hp','m_courier.id_area','m_courier.id_wilayah','m_area.nama_area','m_wilayah.nama_wilayah')
				->selectRaw("(SELECT SUM(fee_courier) FROM t_order WHERE deleted = 1 and id_courier = m_courier.id_courier AND date(tanggal_pemesanan) = '".date('Y-m-d')."') as total_fee_today")
                ->join('m_courier','m_courier.id_courier','m_user.id_ref')
				->leftJoin('m_area','m_courier.id_area','m_area.id_area')
				->leftJoin('m_wilayah','m_courier.id_wilayah','m_wilayah.id_wilayah')
                ->where('m_user.id_user',auth::user()->id_user)
                ->where('m_user.tipe_user',2)
                ->get();
			
        return response()->json([
            'success' => true,
            'message' => 'Login Success',
            'key' => $key,
            'code' => 1,
            'user_data' => $get_user
        ]);
    }
}
