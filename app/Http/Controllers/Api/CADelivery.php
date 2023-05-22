<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MApiKey;
use App\Models\User;
use App\Models\TOrder;
use Illuminate\Support\Facades\Validator;
use DB;

class CADelivery extends Controller
{
    public function get_all_delivery(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                    
        $id_wilayah = $request->id_wilayah;
        $limit = 10;
        $page = ($request->page-1)*$limit;
        // dd($id_wilayah);
        
        $data = TOrder::from('t_order as a')
        ->selectRaw('a.id_order,a.id_customer, a.alamat, a.id_area, a.id_wilayah, IF(a.jenis_pengantaran = 1, "Makan Siang", "Makan Malam") as jenis_pengantaran_new, a.jenis_paket, a.keterangan, a.tanggal_pemesanan, a.id_status,
		b.nama as nama_customer, b.no_hp as no_telfon_customer, c.nama_area as nama_area, d.nama_wilayah as nama_wilayah, e.nama_status')
        ->leftJoin('m_customer as b','a.id_customer','b.id_customer')
        ->leftJoin('m_area as c','a.id_area','c.id_area')
        ->leftJoin('m_wilayah as d','a.id_wilayah','d.id_wilayah')
        ->leftJoin('m_status as e','a.id_status','e.id_status')
        ->where('a.id_status', 1)
        ->whereDate('a.tanggal_pemesanan', date('Y-m-d'))
        ->where('a.deleted', 1)
        ->where('b.deleted', 1)
        ->where('c.deleted', 1)
        ->where('d.deleted', 1)
        ->where('e.deleted', 1)
        ->limit($limit)
        ->offset($page);
        
        $get_total_all_data = TOrder::from('t_order as a')
        ->selectRaw('a.id_order')
        ->leftJoin('m_customer as b','a.id_customer','b.id_customer')
        ->leftJoin('m_area as c','a.id_area','c.id_area')
        ->leftJoin('m_wilayah as d','a.id_wilayah','d.id_wilayah')
        ->leftJoin('m_status as e','a.id_status','e.id_status')
        ->where('a.id_status', 1)
        ->whereDate('a.tanggal_pemesanan', date('Y-m-d'))
        ->where('a.deleted', 1)
        ->where('b.deleted', 1)
        ->where('c.deleted', 1)
        ->where('d.deleted', 1)
        ->where('e.deleted', 1);

        if($id_wilayah != null) {
            $data= $data->where('a.id_wilayah', $id_wilayah);
            $get_total_all_data= $get_total_all_data->where('a.id_wilayah', $id_wilayah);
        }

        $data= $data->get();        
        $get_total_all_data = $get_total_all_data->count();        
        $total_page = 0;
        $hasil_bagi = $get_total_all_data / $limit;
        if(fmod($get_total_all_data, $limit) == 0){
            $total_page = $hasil_bagi;
        }else{
            $total_page = floor($hasil_bagi)+1;
        }
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data,
            'total_data' => count($data),                
            'total_page' => $total_page
        ], 200);
    }

    public function post_pickup(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                    
        $id_order = explode(",",$request->id_order);
        $cek = TOrder::whereIn('id_order',$id_order)->where('id_status','!=',1)->get()->count();
        // dd($cek);
        if ($cek > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup gagal diproses',
                'code' => 2,
            ], 200);
        }
        TOrder::whereIn('id_order',$id_order)->update(['id_courier' => $user->id_ref,'id_status' => 2]);
        return response()->json([
            'success' => true,
            'message' => 'Pickup berhasil',
            'code' => 1,
        ], 200);
    }

    public function get_all_pickup(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                    
        $id_wilayah = $request->id_wilayah;
        $search = $request->search;
        $limit = 10;
        $page = ($request->page-1)*$limit;
        // dd($user->id_ref);
        
        $data = TOrder::from('t_order as a')
        ->selectRaw('a.id_order, a.id_customer, a.alamat, a.id_area, a.id_wilayah, IF(a.jenis_pengantaran = 1, "Makan Siang", "Makan Malam") as jenis_pengantaran_new, a.jenis_paket, a.keterangan, a.tanggal_pemesanan, a.id_status,b.nama as nama_customer, b.no_hp as no_telfon_customer, c.nama_area as nama_area, d.nama_wilayah as nama_wilayah, e.nama_status, f.nama as nama_courier')
        ->leftJoin('m_customer as b','a.id_customer','b.id_customer')
        ->leftJoin('m_area as c','a.id_area','c.id_area')
        ->leftJoin('m_wilayah as d','a.id_wilayah','d.id_wilayah')
        ->leftJoin('m_status as e','a.id_status','e.id_status')
        ->leftJoin('m_courier as f','a.id_courier','f.id_courier')        
        ->where('a.id_status', 2)
        ->where('a.id_courier', $user->id_ref)
        ->whereDate('a.tanggal_pemesanan', date('Y-m-d'))
        ->where('a.deleted', 1)
        ->where('b.deleted', 1)
        ->where('c.deleted', 1)
        ->where('d.deleted', 1)
        ->where('e.deleted', 1)
        ->where('f.deleted', 1);

        if ($request->page != 0) {
            $data = $data->limit($limit)
            ->offset($page);
        }

        $get_total_all_data = TOrder::from('t_order as a')
        ->selectRaw('a.id_order')
        ->leftJoin('m_customer as b','a.id_customer','b.id_customer')
        ->leftJoin('m_area as c','a.id_area','c.id_area')
        ->leftJoin('m_wilayah as d','a.id_wilayah','d.id_wilayah')
        ->leftJoin('m_status as e','a.id_status','e.id_status')
        ->leftJoin('m_courier as f','a.id_courier','f.id_courier')        
        ->where('a.id_status', 2)
        ->where('a.id_courier', $user->id_ref)
        ->whereDate('a.tanggal_pemesanan', date('Y-m-d'))
        ->where('a.deleted', 1)
        ->where('b.deleted', 1)
        ->where('c.deleted', 1)
        ->where('d.deleted', 1)
        ->where('e.deleted', 1)
        ->where('f.deleted', 1);

        if ($search != null) {
            $data = $data->where(function ($query) use ($search) {
                $query->where('b.nama', 'like', '%'.$search.'%')
                      ->orwhere('a.alamat', 'like', '%'.$search.'%')
                      ->orwhere('a.id_order', 'like', '%'.$search.'%');
            });
            $get_total_all_data = $get_total_all_data->where(function ($query) use ($search) {
                $query->where('b.nama', 'like', '%'.$search.'%')
                      ->orwhere('a.alamat', 'like', '%'.$search.'%')
                      ->orwhere('a.id_order', 'like', '%'.$search.'%');
            });
        }

        if ($id_wilayah != null) {
            $data= $data->where('a.id_wilayah', $id_wilayah);
            $get_total_all_data= $get_total_all_data->where('a.id_wilayah', $id_wilayah);
        }

        $data= $data->get();        
        $get_total_all_data = $get_total_all_data->count();        
        $total_page = 0;
        $hasil_bagi = $get_total_all_data / $limit;
        if(fmod($get_total_all_data, $limit) == 0){
            $total_page = $hasil_bagi;
        }else{
            $total_page = floor($hasil_bagi)+1;
        }
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data,
            'total_data' => count($data),                
            'total_page' => $total_page
        ], 200);
    }

    public function post_done_pickup(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                    
        $id_order = $request->id_order;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $note = $request->note;   

        if(!$request->hasFile('foto')) {            
            return response()->json([
                'success' => false,
                'message' => 'upload file not found',
                'code' => 0,
            ], 400);
        }

        $file = $request->file('foto');
        $validator = Validator::make($request->all(), [
            'foto' => 'mimes:jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'file upload harus gambar',
                'code' => 0,
            ], 400);
        }

        $path = public_path().'/upload/foto';
        $foto = round(microtime(true) * 1000).'.'.$file->extension();
        $file->move($path, $foto);
		$this->compress(public_path('/upload/foto/'.$foto),public_path('/upload/foto/'.$foto));
        
        // dd($foto);
        TOrder::where('id_order',$id_order)->update([
            'waktu_courier_tiba' => date('Y-m-d H:i:s'),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'foto_bukti' => $foto,
            'catatan_courier' => $note,
            'id_status' => 3,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Pickup berhasil',
            'code' => 1,
        ], 200);
    }

    public function get_delivery_fee_history(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                    
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $limit = 10;
        $page = ($request->page-1)*$limit;
        // dd($user->id_ref);
        
        $data = TOrder::from('t_order as a')
        ->selectRaw('a.id_order, a.id_customer, a.alamat, a.id_area, a.id_wilayah, IF(a.jenis_pengantaran = 1, "Makan Siang", "Makan Malam") as jenis_pengantaran_new, a.jenis_paket, a.keterangan, a.tanggal_pemesanan, a.id_status, a.fee_courier,b.nama as nama_customer, b.no_hp as no_telfon_customer, c.nama_area as nama_area, d.nama_wilayah as nama_wilayah, e.nama_status, f.nama as nama_courier')
        ->leftJoin('m_customer as b','a.id_customer','b.id_customer')
        ->leftJoin('m_area as c','a.id_area','c.id_area')
        ->leftJoin('m_wilayah as d','a.id_wilayah','d.id_wilayah')
        ->leftJoin('m_status as e','a.id_status','e.id_status')
        ->leftJoin('m_courier as f','a.id_courier','f.id_courier')        
        ->where('a.id_status', 4)
        ->where('a.id_courier', $user->id_ref)
        ->whereBetween(DB::raw('DATE(tanggal_pemesanan)'), [$tanggal_awal,$tanggal_akhir])
        ->where('a.deleted', 1)
        ->where('b.deleted', 1)
        ->where('c.deleted', 1)
        ->where('d.deleted', 1)
        ->where('e.deleted', 1)
        ->where('f.deleted', 1)
        ->limit($limit)
        ->offset($page);

        $get_total_all_data = TOrder::from('t_order as a')
        ->selectRaw('a.id_order')
        ->leftJoin('m_customer as b','a.id_customer','b.id_customer')
        ->leftJoin('m_area as c','a.id_area','c.id_area')
        ->leftJoin('m_wilayah as d','a.id_wilayah','d.id_wilayah')
        ->leftJoin('m_status as e','a.id_status','e.id_status')
        ->leftJoin('m_courier as f','a.id_courier','f.id_courier')        
        ->where('a.id_status', 4)
        ->where('a.id_courier', $user->id_ref)
        ->whereBetween(DB::raw('DATE(tanggal_pemesanan)'), [$tanggal_awal,$tanggal_akhir])
        ->where('a.deleted', 1)
        ->where('b.deleted', 1)
        ->where('c.deleted', 1)
        ->where('d.deleted', 1)
        ->where('e.deleted', 1)
        ->where('f.deleted', 1);

        $data= $data->get();        
        $get_total_all_data = $get_total_all_data->count();        
        $total_page = 0;
        $hasil_bagi = $get_total_all_data / $limit;
        if(fmod($get_total_all_data, $limit) == 0){
            $total_page = $hasil_bagi;
        }else{
            $total_page = floor($hasil_bagi)+1;
        } 
    
        $total_fee = TOrder::where('deleted', 1)
            ->where('id_status', 4)
            ->where('id_courier', $user->id_ref)
            ->whereBetween(DB::raw('DATE(tanggal_pemesanan)'), [$tanggal_awal,$tanggal_akhir])
            ->sum('fee_courier');
          
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data,
            'total_data' => count($data),                
            'total_page' => $total_page,
            'total_fee' => $total_fee
        ], 200);
    }

    public function compress($source, $destination, $quality = 10) {
		// dd($source);
        $info = getimagesize($source);
        if ($info['mime'] == 'image/jpeg') {
          $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/gif') {
          $image = imagecreatefromgif($source);
        } elseif ($info['mime'] == 'image/png') {
          $image = imagecreatefrompng($source);
        }
        imagejpeg($image, $destination, $quality);
        return $destination;
    }

    public function get_total_fee_today(Request $request)
    {        
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                          
        
        $total_fee = TOrder::where('deleted', 1)
            ->where('id_status', 4)
            ->where('id_courier', $user->id_ref)
            ->whereDate('tanggal_pemesanan', date('Y-m-d'))
            ->sum('fee_courier');

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'total_fee' => $total_fee
        ]);
    }
}
