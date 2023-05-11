<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MApiKey;
use App\Models\TAbsensi;
use App\Models\TLembur;
use App\Models\TIzinnCuti;
use App\Models\TIzinDetail;
use App\Models\MRole;
use App\Models\MapApprIzin;
use App\Models\Notif;
use App\Models\RefTemplateNotif;
use App\Models\RefTipeAbsensi;
use App\Models\MKaryawan;
use App\Models\User;
use App\Models\LogSelfi;
use App\Models\MLokasi;
use App\Models\LokasiChecklog;
use App\Models\MSetting;
use App\Models\MShiftKaryawan;
use Illuminate\Support\Facades\Validator;

use DateInterval;
use DatePeriod;
use DateTime;

class CAAbsen extends Controller
{
    public function history_absen(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();
        $start = $request->tanggal_mulai;
        $end = $request->tanggal_akhir;

        if ($start == null && $end == null) {
            $start = date('Y/m/01');
            $end = date('Y/m/d');
        }

        $m = TAbsensi::from('t_absensi as a')
            ->leftJoin('ref_tipe_absensi as b','a.id_tipe_absensi','=','b.id_tipe_absensi')
            ->select('a.*','b.nama_tipe_absensi')
            ->whereBetween('a.tanggal', [$start, $end])
            ->where('a.id_karyawan',$user->id_karyawan)
            ->orderBy('a.tanggal','asc')
            ->get()->toArray();

        $masuk = TAbsensi::from('t_absensi as a')            
            ->select('a.id_absensi')
            ->whereBetween('a.tanggal', [$start, $end])
            ->where('a.id_karyawan',$user->id_karyawan)
            ->where('a.id_tipe_absensi',1)            
            ->get()->count();

        $terlambat = TAbsensi::from('t_absensi as a')            
            ->select('a.id_absensi')
            ->whereBetween('a.tanggal', [$start, $end])
            ->where('a.id_karyawan',$user->id_karyawan)            
            ->sum('a.menit_terlambat');

        $early_leave = TAbsensi::from('t_absensi as a')            
            ->select('a.id_absensi')
            ->whereBetween('a.tanggal', [$start, $end])
            ->where('a.id_karyawan',$user->id_karyawan)            
            ->sum('a.menit_early_leave');
        $summary =[
            'masuk' => $masuk,
            'terlambat' => $terlambat,
            'early_leave' => $early_leave,
        ];
        $tipe_absensi = RefTipeAbsensi::where('deleted',1)->get();
        
        $data = [];
        $period = new DatePeriod(
            new DateTime($start),
            new DateInterval('P1D'),
            new DateTime($end.' +1 days')
        );

        foreach($period as $key => $value){            
            $hsl = array_search($value->format('Y-m-d'), array_column($m, 'tanggal'));
            if ($hsl !== false) {
                $data[$key]['tanggal'] = $m[$hsl]["tanggal"];
                $data[$key]['masuk'] = $m[$hsl]["tanggal_masuk"];
                $data[$key]['keluar'] = $m[$hsl]["tanggal_keluar"];
                $data[$key]['terlambat'] = $m[$hsl]["menit_terlambat"];
                $data[$key]['early_leave'] = $m[$hsl]["menit_early_leave"];
                $data[$key]['nama_tipe_absensi'] = $m[$hsl]["nama_tipe_absensi"];
                $data[$key]['id_tipe_absensi'] = $m[$hsl]["id_tipe_absensi"];
            }else{
                $data[$key]['tanggal'] = $value->format('Y-m-d');
                $data[$key]['masuk'] = null;
                $data[$key]['keluar'] = null;
                $data[$key]['terlambat'] = null;
                $data[$key]['early_leave'] = null;
                $data[$key]['nama_tipe_absensi'] = "tidak masuk";
                $data[$key]['id_tipe_absensi'] = "0";
            }
        }

        // dd($data);
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data,
            'summary' => $summary,
            'tipe_absensi' => $tipe_absensi
        ], 200);
    }    

    public function list_lembur(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();

        $start = $request->tanggal_mulai;
        $end = $request->tanggal_akhir;

        $m = TLembur::where('t_lembur.deleted',1)
            ->selectRaw('t_lembur.approval,t_lembur.approval2,t_lembur.approval3,sum(jumlah_jam) as total_jam,m_karyawan.nama_karyawan,t_lembur.tanggal,m_karyawan.id_karyawan, t_lembur.alasan_lembur')
            ->join('m_karyawan','m_karyawan.id_karyawan','=','t_lembur.id_karyawan','left')
            ->groupBy('m_karyawan.id_karyawan','m_karyawan.nama_karyawan','tanggal','t_lembur.approval')
            ->whereBetween('t_lembur.tanggal',[$start, $end])
            ->where('t_lembur.id_karyawan',$user->id_karyawan)
            ->get();


        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $m
        ], 200);
    }

    public function riwayat_cuti(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();

        // $bulan = $request->bulan;
        // $tahun = $request->tahun;

        $model = TIzinnCuti::select("t_izin.*",'m_karyawan.nama_karyawan','ref_tipe_absensi.nama_tipe_absensi')
            ->join('m_karyawan','m_karyawan.id_karyawan','=','t_izin.id_karyawan','left')
            ->join('ref_tipe_absensi','ref_tipe_absensi.id_tipe_absensi','=','t_izin.id_tipe_absensi','left')
            ->where('t_izin.deleted',1)
            ->where('t_izin.id_karyawan',$user->id_karyawan)
            ->orderBy('t_izin.created_date','desc');
        // if ($bulan != null) {
        //     $model = $model->whereMonth('t_izin.tanggal_mulai',$bulan);
        // }
        // if ($tahun != null) {
        //     $model = $model->whereYear('t_izin.tanggal_mulai',$tahun);
        // }
        $model = $model->get();
        foreach ($model as $key => $value) {
            $appr = MapApprIzin::where('id_izin',$value['id_izin'])->where('approval',0)->first();
            if ($appr != null) {
                $value->approved = 0;
            }else {
                $value->approved = 1;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $model
        ], 200);
    }    
    
    public function pengajuan_cuti(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();

        $id_tipe_absensi = $request->id_tipe_absensi;
        $alasan = $request->alasan;
        $start = $request->tanggal_mulai;
        $end = $request->tanggal_selesai;  

        $mCuti = new TIzinnCuti;
        $mCuti->id_tipe_absensi = $id_tipe_absensi;
        $mCuti->id_karyawan = $user->id_karyawan;
        $mCuti->tanggal_mulai = date('Y-m-d',strtotime($start));
        $mCuti->tanggal_selesai = date('Y-m-d',strtotime($end));
        $mCuti->alasan = $alasan;
        $mCuti->approval = 0;
        $mCuti->approve_by = 0;
        $mCuti->created_by = 0;
        $mCuti->updated_by = 0;
        $mCuti->save();

        $period = new DatePeriod(
            new DateTime($request->tanggal_mulai),
            new DateInterval('P1D'),
            new DateTime($request->tanggal_selesai.' +1 days')
        );
            
        foreach($period as $key){
            $absensi = new TIzinDetail;
            $absensi->id_karyawan = $user->id_karyawan;
            $absensi->tanggal = $key->format('Y-m-d');            
            $absensi->id_izin = $mCuti->id_izin;
            $absensi->save();
        }

        $urutan = MRole::withDeleted()->where('id_role',$user->id_role)->first();
        $list_approval = MRole::withDeleted()
            ->where('urutan_approval_cuti','>=',$urutan->urutan_approval_cuti)
            ->groupBy('urutan_approval_cuti')
            ->orderBy('urutan_approval_cuti','ASC')
            ->get();
        foreach ($list_approval as $key => $value) {
            if ($key==0) {
                $appr = new MapApprIzin;
                $appr->id_izin = $mCuti->id_izin;
                $appr->id_role = $value->id_role;
                $appr->urutan = $value->urutan_approval_cuti;
                $appr->approval = 1;
                $appr->approve2_by = $user->id_user;
                $appr->approve_date = date('Y-m-d H:i:s');
                $appr->save();
            }elseif ($key==1) {
                $appr = new MapApprIzin;
                $appr->id_izin = $mCuti->id_izin;
                $appr->id_role = $value->id_role;
                $appr->urutan = $value->urutan_approval_cuti;
                $appr->approval = 0;
                $appr->approve2_by = 0;                
                $appr->save();
                $cekrole = MRole::where('id_role',$value->id_role)->first();
                if ($cekrole->kode_role == "asman" || $cekrole->kode_role == "manager") {
                    
                    $departemen = MKaryawan::select('id_departemen')->where('id_karyawan',$user->id_karyawan)->first();
                    $userA = MKaryawan::join('m_users','m_users.id_karyawan','=','m_karyawan.id_karyawan')                                
                                    ->where('m_karyawan.id_departemen',$departemen->id_departemen)
                                    ->where('m_users.id_role',$value->id_role)
                                    ->get(); 
                }else{
                    $userA = User::where('id_role',$value->id_role)->where('deleted',1)->get();                    
                }                   
                    $ref_notif = RefTemplateNotif::where('kode','approval_izin')->where('deleted',1)->first();
                    // dd($userA);
                    foreach ($userA as $values) {                    
                        $abs = RefTipeAbsensi::where('id_tipe_absensi',$request->id_tipe_absensi)->first();
                        $kar = MKaryawan::where('id_karyawan',$request->id_karyawan)->first();
                        $isi = $ref_notif->isi;
                        $isi = str_replace("{nama_karyawan}",$user->id_karyawan,$isi);
                        $isi = str_replace("{tipe_absensi}",$abs->nama_tipe_absensi,$isi);
                        $isi = str_replace("{tanggal_mulai}",$request->tanggal_mulai,$isi);
                        $isi = str_replace("{tanggal_selesai}",$request->tanggal_selesai,$isi);
                        $isi = str_replace("{alasan}",$request->alasan,$isi);
                        
                        $not = new Notif;
                        $not->id_user = $values->id_user;
                        $not->judul = $ref_notif->judul;
                        $not->url = "absensi/izin-cuti";
                        $not->isi = $isi;
                        $not->is_read = 0;
                        $not->deleted = 1;
                        $not->save();
    
                        $new = User::find($values->id_user);                    
                        $new->new_notif = $values->new_notif + 1;
                        $new->update();
    
                        // Mail::to($values->email)->send(new Email_notif($values->name,$ref_notif->judul,$isi,"absensi/izin-cuti"));
                    }
                
            }else {
                $appr = new MapApprIzin;
                $appr->id_izin = $mCuti->id_izin;
                $appr->id_role = $value->id_role;
                $appr->urutan = $value->urutan_approval_cuti;
                $appr->approval = 0;
                $appr->approve2_by = 0;                
                $appr->save();
            }
        }
        if (count($list_approval)==1) {

            $Cuti = TIzinnCuti::find($mCuti->id_izin);

            TAbsensi::where('id_karyawan',$Cuti->id_karyawan)->whereBetween('tanggal',[$Cuti->tanggal_mulai,$Cuti->tanggal_selesai])->update(['deleted' => 0]);
            
            // TAbsensi::where('id_karyawan',$Cuti->id_karyawan)->where('id_tipe_absensi',$Cuti->id_tipe_absensi)->delete();
            TAbsensi::where('id_karyawan',$Cuti->id_karyawan)->where('id_izin',$Cuti->id_izin)->delete();
            
            
            $period = new DatePeriod(
                new DateTime($Cuti->tanggal_mulai),
                new DateInterval('P1D'),
                new DateTime($Cuti->tanggal_selesai.' +1 days')
            );
            
            foreach($period as $key){
                $absensi = new TAbsensi;
                $absensi->id_karyawan = $Cuti->id_karyawan;
                $absensi->tanggal = $key->format('Y-m-d');
                $absensi->id_tipe_absensi = $Cuti->id_tipe_absensi;
                $absensi->id_izin = $Cuti->id_izin;
                $absensi->save();
            }   
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan Cuti Success',
            'code' => 1,
        ], 200);
    }

    public function clockin(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();

        if ($user->is_mobile_active == 0) {
            return response()->json([
                'success' => false,
                'message' => "Anda Tidak Punya Akses",
                'code' => 0,
                'akses' => 0
            ], 400);
        }

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

        $cek = LokasiChecklog::join('m_lokasi','m_lokasi.id_lokasi','lokasi_checklog.lokasi_in')
            ->select('m_lokasi.latitude','m_lokasi.longitude','lokasi_checklog.*')
            ->where('id_karyawan',$user->id_karyawan)
            ->where('tanggal',date('Y-m-d'))
            ->where('lokasi_checklog.deleted',1)->first();

        if ($cek) {
            $max = MSetting::where('kode','radius_checklock')->first();
            $jarak = $this->haversineDistance($cek->latitude,$cek->longitude,$request->latitude,$request->longitude);
            if ($jarak > $max->nilai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jarak anda dari lokasi absensi adalah '.$jarak.' meter, batas maksimal jarak absensi adalah '.$max->nilai.' meter',
                    'code' => 0,
                ], 400);
            }            
        }

        $path = public_path().'/upload/foto';
        $foto = round(microtime(true) * 1000).'.'.$file->extension();
        $file->move($path, $foto);
		$this->compress(public_path('/upload/foto/'.$foto),public_path('/upload/foto/'.$foto));
        // $request->file('foto')->move(public_path('upload/foto'), $foto);

        $mCuti = new LogSelfi;
        $mCuti->id_karyawan = $user->id_karyawan;
        $mCuti->jam_selfi = $request->jam_selfi;
        $mCuti->type = 0;
        $mCuti->latitude = $request->latitude;
        $mCuti->longitude = $request->longitude;
        $mCuti->foto = $foto;
        $mCuti->status = 0;
        $mCuti->save();

        return response()->json([
            'success' => true,
            'message' => 'Absensi Success',
            'code' => 1,
        ], 200);
    }    

    public function clockout(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();

        if ($user->is_mobile_active == 0) {
            return response()->json([
                'success' => false,
                'message' => "Anda Tidak Punya Akses",
                'code' => 0,
                'akses' => 0
            ], 400);
        }

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

        $cek = LokasiChecklog::join('m_lokasi','m_lokasi.id_lokasi','lokasi_checklog.lokasi_out')
            ->select('m_lokasi.latitude','m_lokasi.longitude','lokasi_checklog.*')
            ->where('id_karyawan',$user->id_karyawan)
            ->where('tanggal',date('Y-m-d'))
            ->where('lokasi_checklog.deleted',1)->first();

        if ($cek) {
            $max = MSetting::where('kode','radius_checklock')->first();
            $jarak = $this->haversineDistance($cek->latitude,$cek->longitude,$request->latitude,$request->longitude);
            if ($jarak > $max->nilai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jarak anda dari lokasi absensi adalah '.$jarak.' meter, batas maksimal jarak absensi adalah '.$max->nilai.' meter',
                    'code' => 0,
                ], 400);
            }            
        }

        $path = public_path().'/upload/foto';
        $foto = round(microtime(true) * 1000).'.'.$file->extension();
        $file->move($path, $foto);
		$this->compress(public_path('/upload/foto/'.$foto),public_path('/upload/foto/'.$foto));

        $mCuti = new LogSelfi;
        $mCuti->id_karyawan = $user->id_karyawan;
        $mCuti->jam_selfi = $request->jam_selfi;
        $mCuti->type = 1;
        $mCuti->latitude = $request->latitude;
        $mCuti->longitude = $request->longitude;
        $mCuti->foto = $foto;
        $mCuti->status = 0;
        $mCuti->save();

        return response()->json([
            'success' => true,
            'message' => 'Absensi Success',
            'code' => 1,
        ], 200);
    }

    public function get_status_absen(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();        

        $status = LogSelfi::whereDate('jam_selfi', date('Y-m-d'))->where('id_karyawan',$user->id_karyawan)->orderBy('jam_selfi','desc')->first();
        if ($status) {
            if ($status->type == 0) {
                $data = 'Masuk';
            }elseif ($status->type == 1) {
                $data = 'Keluar';
            }
        }else {
            $data ='Belum_absen';
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'status' => $data,
            'code' => 1,
        ], 200);
    }
    
    public function get_jam(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();   
        
        if ($user->is_mobile_active == 0) {
            return response()->json([
                'success' => false,
                'message' => "Anda Tidak Punya Akses",
                'code' => 0,
                'akses' => 0
            ], 400);
        }
        
        $status = LogSelfi::whereDate('jam_selfi', date('Y-m-d'))->where('id_karyawan',$user->id_karyawan)->orderBy('jam_selfi','desc')->first();
        if ($status) {
            if ($status->type == 0) {
                $data = 'Masuk';
            }elseif ($status->type == 1) {
                $data = 'Keluar';
            }
        }else {
            $data ='Belum_absen';
        }
        
        $in = LogSelfi::whereDate('jam_selfi', date('Y-m-d'))->where('id_karyawan',$user->id_karyawan)->where('type',0)->orderBy('jam_selfi','desc')->first();
        $out = LogSelfi::whereDate('jam_selfi', date('Y-m-d'))->where('id_karyawan',$user->id_karyawan)->where('type',1)->orderBy('jam_selfi','desc')->first();        

        if ($in != null) {
            $clockin = date('H:i',strtotime($in->jam_selfi));
        }else {
            $clockin = '-';
        }

        if ($out) {
            $clockout = date('H:i',strtotime($out->jam_selfi));
        }else {
            $clockout = '-';
        }

        if ($in != null && $out != null) {                        
            $time1 = new DateTime($out->jam_selfi);
            $time2 = new DateTime($in->jam_selfi);
            $time_diff = $time1->diff($time2);
            if ($time_diff->h <= 9) {
                $jam = '0'.$time_diff->h;
            }else {
                $jam = $time_diff->h;
            }
            if ($time_diff->i <= 9) {
                $menit = '0'.$time_diff->i;
            }else{
                $menit = $time_diff->i;
            }
            $selisih = $jam.':'.$menit;
        }elseif ($in != null && $out == null) {            
            $time1 = new DateTime(date('Y-m-d H:i:s'));
            $time2 = new DateTime($in->jam_selfi);
            $time_diff = $time1->diff($time2);
            if ($time_diff->h <= 9) {
                $jam = '0'.$time_diff->h;
            }else {
                $jam = $time_diff->h;
            }
            if ($time_diff->i <= 9) {
                $menit = '0'.$time_diff->i;
            }else{
                $menit = $time_diff->i;
            }
            $selisih = $jam.':'.$menit;
            // dd($selisih);
        }else{
            $selisih = '-';
        } 

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'clockin' => $clockin,
            'detail_in' => $in,
            'clockout' => $clockout,
            'detail_out' => $out,
            'working_hr' => $selisih,
            'status' => $data,
            'akses' => $user->is_mobile_active,
            'code' => 1,
        ], 200);
    }

    public function get_tipe_absensi(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();

        $tipeAbsensi = RefTipeAbsensi::withDeleted()->where('is_show','=',1)->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $tipeAbsensi
        ], 200);
    }    

    function haversineDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distance = $earthRadius * $c;

        return floor($distance);
    }

    public function get_shift(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();
        $start = $request->tanggal_mulai;
        $end = $request->tanggal_akhir;
        
        $data = [];
        if ($start != null && $end != null) {
            # code...
            $period = new DatePeriod(
                new DateTime($start),
                new DateInterval('P1D'),
                new DateTime($end.' +1 days')
            );        
            foreach($period as $key => $value){ 
                $shift = MShiftKaryawan::join('m_shift','m_shift.id_shift','m_shift_karyawan.id_shift')
                    ->select('m_shift.*','m_shift_karyawan.*')
                    ->where('m_shift_karyawan.tanggal',$value->format('Y-m-d'))
                    ->where('m_shift_karyawan.id_karyawan',$user->id_karyawan)
                    ->first();
                    $tanggal = '-';
                    $kode_shift = '-';
                    $nama_shift = '-';
                    $jam_masuk = '-';
                    $jam_pulang = '-';
                    if ($shift) {
                        $tanggal = $shift->tanggal;
                        $kode_shift = $shift->kode_shift;
                        $nama_shift = $shift->nama_shift;
                        $jam_masuk = $shift->jam_masuk;
                        $jam_pulang = $shift->jam_keluar;
                    }
                $lokasi = LokasiChecklog::join('m_lokasi as a','a.id_lokasi','lokasi_checklog.lokasi_in')
                    ->join('m_lokasi as b','b.id_lokasi','lokasi_checklog.lokasi_out')
                    ->select('a.nama_lokasi as in','b.nama_lokasi as out','lokasi_checklog.*')
                    ->where('lokasi_checklog.tanggal',$value->format('Y-m-d'))
                    ->where('lokasi_checklog.id_karyawan',$user->id_karyawan)
                    ->first();
                    $in = '-';
                    $out = '-';
                    if ($lokasi) {
                        $in = $lokasi->in;
                        $out = $lokasi->out;
                    }
                // $lokasi = 
                $data[$key]['tanggal'] = $value->format('Y-m-d');
                $data[$key]['kode_shift'] = $kode_shift;
                $data[$key]['nama_shift'] = $nama_shift;
                $data[$key]['jam_masuk'] = $jam_masuk;
                $data[$key]['jam_pulang'] = $jam_pulang;
                $data[$key]['lokasi_clockin'] = $in;
                $data[$key]['lokasi_clockout'] = $out;
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data
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
}
