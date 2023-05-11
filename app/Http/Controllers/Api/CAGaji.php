<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MPeriode;
use App\Models\TGajiKaryawanPeriode;
use App\Models\MApiKey;
use App\Models\User;
use DB;

class CAGaji extends Controller
{
    public function slip_gaji(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();                    
        $tahun = $request->tahun;
        
        $data = TGajiKaryawanPeriode::join('m_periode','m_periode.id_periode','t_gaji_karyawan_periode.id_periode')
        ->select('t_gaji_karyawan_periode.*','m_periode.bulan','m_periode.tahun')
        ->where('t_gaji_karyawan_periode.deleted',1)
        ->where('t_gaji_karyawan_periode.id_karyawan',$user->id_karyawan);
        
        if ( $tahun != null) {
            $data = $data->where('m_periode.tahun',$tahun);
        }
        $data = $data->get();
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'data' => $data
        ], 200);
    }

    public function detail_slip_gaji(Request $request)
    {
        $token = MApiKey::where('token',$request->header('auth-key'))->first();
        $user = User::where('id_user',$token->id_user)->first();
        $id = $request->id_gaji;

        $m_gaji_p = TGajiKaryawanPeriode::join('m_periode','m_periode.id_periode','t_gaji_karyawan_periode.id_periode')
            ->join('m_karyawan','m_karyawan.id_karyawan','t_gaji_karyawan_periode.id_karyawan')
            ->join('m_departemen','m_departemen.id_departemen','m_karyawan.id_departemen_label')
            ->join('m_jabatan','m_jabatan.id_jabatan','m_karyawan.id_jabatan')
            ->select('t_gaji_karyawan_periode.*','m_karyawan.nama_karyawan','m_karyawan.nik','m_periode.bulan','m_periode.tahun','m_departemen.nama_departemen','m_jabatan.nama_jabatan')
            ->where('t_gaji_karyawan_periode.id',$id)->first();

        // $hari=date("Y-m-d", strtotime($m_gaji_p->tahun."-".$m_gaji_p->bulan."-10"));

        // $hari1=date('Y-m-d', strtotime('-1 month', strtotime( $hari )));
        // $hariawal=date('Y-m-d', strtotime('+1 days', strtotime( $hari1 )));

        // $query = DB::table('t_gaji_karyawan_periode_lembur')
        // ->select('index_tarif')
        // ->selectRaw("SUM(jumlah_jam) as jam")
        // ->where('deleted',1)
        // ->whereBetween('tanggal', [$hariawal, $hari])
        // ->where('id_gaji_karyawan_periode',$id)
        // ->groupBy('index_tarif')->get();

        // $gaji_pokok = DB::table('t_gaji_karyawan_periode_det')
        // ->where('deleted',1)
        // ->where('id_gaji_karyawan_periode',$id)
        // ->where('id_gaji',1)
        // ->first();

        // $gaji_per_jam = $gaji_pokok->nominal / 173;
        // $tot_a=0;
        // $lembur = [];
        // foreach ($query as $aa) {
        //     $hsl=$aa->jam * $gaji_per_jam * $aa->index_tarif;
        //     $arr_lembur = [
        //         'index_tarif' => $aa->index_tarif,
        //         'total_jam' => $aa->index_tarif,
        //         'nominal' => $hsl
        //     ];
        //     array_push($lembur,$arr_lembur);
        // }
        $gaji_basic = DB::table('t_gaji_karyawan_periode_det')
            ->select('nama_gaji','nominal')
            ->where('deleted',1)
            ->where('id_gaji_karyawan_periode',$id)
            ->where('nominal','>',0)
            ->get()->toArray();
        $arrayName5 = array(
            'nama_gaji' => 'Lembur','nominal' => $m_gaji_p->lembur,
        );
        array_push($gaji_basic,$arrayName5);

        $deduction= DB::table('t_gaji_karyawan_periode_det')
            ->select('nama_gaji','nominal')
            ->where('deleted',1)
            ->where('id_gaji_karyawan_periode',$id)
            ->where('nominal','<',0)
            ->get();
        $gaji_deduction=[];
        foreach ($deduction as $value) {
            $Name1 = array(
                'nama_gaji' => $value->nama_gaji,
                'nominal' => intval(str_replace("-","",$value->nominal)),
            );    
            array_push($gaji_deduction,$Name1);
        }
        $arrayName1 = array(
            'nama_gaji' => 'BPJS JHT','nominal' => $m_gaji_p->jht_karyawan,
        );
        $arrayName2 = array(            
            'nama_gaji' => 'BPJS Pensiun','nominal' => $m_gaji_p->jpn_karyawan,
        );
        $arrayName3 = array(            
            'nama_gaji' => 'BPJS Kesehatan','nominal' => $m_gaji_p->jkn_karyawan,            
        );
        $arrayName4 = array(            
            'nama_gaji' => 'PPH 21','nominal' => $m_gaji_p->pph21_sebulan,
        );
        array_push($gaji_deduction,$arrayName1);
        array_push($gaji_deduction,$arrayName2);
        array_push($gaji_deduction,$arrayName3);
        array_push($gaji_deduction,$arrayName4);
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'code' => 1,
            'gaji' => $m_gaji_p,            
            'basic_salary' => $gaji_basic,
            'deduction' => $gaji_deduction,
        ], 200);
    }
}
