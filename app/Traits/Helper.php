<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Auth;

trait Helper
{
    public static function convertDate($tgl, $tampil_hari=true, $with_menit = true){
        if ($tgl != null ||  $tgl != "") {
                $nama_hari    =   array("Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jum'at", "Sabtu");
                $nama_bulan   =   array (
                                    1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus","September", "Oktober", "November", "Desember"
                                );
                $tahun        =   substr($tgl,0,4);
                $bulan        =   $nama_bulan[(int)substr($tgl,5,2)];
                $tanggal      =   substr($tgl,8,2);

                $text         =   "";

                if ($tampil_hari) {

                    $urutan_hari  =   date('w', mktime(0,0,0, substr($tgl,5,2), $tanggal, $tahun));
                    $hari         =   $nama_hari[$urutan_hari];
                    $text         .=  $hari.", ";

                }

                $text         .=$tanggal ." ". $bulan ." ". $tahun;

                if ($with_menit) {

                $jam    =   substr($tgl,11,2);
                $menit  =   substr($tgl,14,2);

                $text   .=  ", ".$jam.":".$menit;

                }


        }else{

            $text = "-";
        }
    return $text;
    }
    function ribuan($angka,$comma = 0){

      $hasil_rupiah = number_format($angka,$comma,',','.');
      return $hasil_rupiah;
     
    }
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public static function searchData($column1,$column2,$table,$value)
    {
        $listDefault = DB::table($table)->where('deleted',1)->get([$column1,$column2]);
        $result = "";

        foreach($listDefault as $key){
            if($key->{$column1} == $value){
                $result = $key->{$column2};
                return $result;
            }
            
        }
        return $result;
    }
    function showData($data,$column){
        $columnDate = ['tanggal_start','tanggal_end'];

        if($data != null){
                return $data->{$column};
            
        }else{
            if(old($column)){
                if(in_array($column,$columnDate)){
                    return date('Y-m-d',strtotime(old($column)));
                }else{
                    return old($column);
                }
            }
        }
    }
    function showDataDate($data,$column){
        $columnDate = ['tanggal_start','tanggal_end'];
        if($data != null){
            if(strtotime($data->{$column})){
                return date('Y-m-d',strtotime($data->{$column}));
            }else{
                return $data->{$column};
            }
        }else{
            if(old($column)){
                if(in_array($column,$columnDate)){
                    return date('Y-m-d',strtotime(old($column)));
                }else{
                    return old($column);
                }
            }
        }
    }
    function showDataSelected($data,$column,$value){
        if($data != null){
            if($data->{$column} == $value){
                return 'selected';
            };
        }
    }
    function showDataSelected2($data,$column,$value){
        if($data !== null){
            if($data->{$column} === $value){
                return 'selected';
            };
        }
    }
    function showDataSelect2($data,$column1,$column2,$table){
        if($data != null){
            // dd("dsadas");
            $result = Helper::searchData($column1,$column2,$table,$data->{$column1});
            
            return "<option value='{$data->{$column1}}' selected>".$result."</option>";
        }else{
            if(old($column1)){
                $result = Helper::searchData($column1,$column2,$table,old($column1));
                return "<option value='".old($column1)."' selected>".$result."</option>";
            }
        }
    }
    function showDataChecked($data,$column,$value){
        if($data != null){
            if($data->{$column} == $value){
                return 'checked';
            };
        }
    }

    function showDataChecked2($data,$column,$value){
        if($data != null){
            foreach ($data as $a) {
                if($a->{$column} == $value){
                    return 'checked';
                };
            }
        }
    }

    function singkatAngka($n, $presisi=1) {
        if ($n < 900) {
            $format_angka = number_format($n, $presisi);
            $simbol = '';
        } else if ($n < 900000) {
            $format_angka = number_format($n / 1000, $presisi);
            $simbol = 'rb';
        } else if ($n < 900000000) {
            $format_angka = number_format($n / 1000000, $presisi);
            $simbol = 'jt';
        } else if ($n < 900000000000) {
            $format_angka = number_format($n / 1000000000, $presisi);
            $simbol = 'M';
        } else {
            $format_angka = number_format($n / 1000000000000, $presisi);
            $simbol = 'T';
        }
    
        if ( $presisi > 0 ) {
            $pisah = '.' . str_repeat( '0', $presisi );
            $format_angka = str_replace( $pisah, '', $format_angka );
        }
        
        return $format_angka . $simbol;
    }
    public function replaceNumeric($nominal)
    {
        return str_replace(".","",$nominal);
    }
    public function convertBulan($value)
    {
        if($value != null){
            $nama_bulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus","September", "Oktober", "November", "Desember"];
        }else{
            return "-";
        }
        return $nama_bulan[$value-1];
    }
    public function convertBulanEn($value)
    {
        if($value != null){
            $nama_bulan = ["January", "February", "March", "April", "May", "June", "July", "August","September", "Oktober", "November", "Desember"];
        }else{
            return "-";
        }
        return $nama_bulan[$value-1];
    }
    public function setSessionPeriode($periode) //this model paramaters
    {
        Session::put('periode',($periode->bulan ?? null).($periode->tahun ?? null));
        Session::put('id_periode',$periode->id_periode ?? null);
        Session::put('periode_bulan',$periode->bulan ?? null);
        Session::put('periode_tahun',$periode->tahun ?? null);
    }
    public function getMinutes($value)
    {
        //15 - 23
        $hours = date("H",$value);
        $minutes = date("i",$value);
        $seconds = date("s",$value);
        $result = 0;
        if(intval($hours) > 0){
            $result += intval($hours) * 60;
        }
        if($minutes > 0){
            $result += intval($minutes);
        }
        if($seconds > 0){
            $result += intval($seconds) / 60;
        }
        return $result;

    }
    public function calculateDistanceMinutes($value1,$value2)
    {
        //15:00:00 -- 23:00:00
        //23 - 15 = 8
        // $value1 = date("H:i:s",strtotime("01/01/2022 11:00:00 PM"));
        // $value2 = date("H:i:s",strtotime("02/01/2022 07:00:00 AM"));
        // dd($value1);
        $time = strtotime($value2) - strtotime($value1);
        
        $minutes = $this->getMinutes($time);
        return $minutes;
    }

   public function can_akses($kode = null) {

  $data_akses   =   DB::table('m_action as a')
                    ->select('a.kode')
                    ->join('map_role_action as b','a.id_action','=','b.id_action')
                    ->where('b.id_role',Auth::user()->id_role)->get();

  // dd($data_akses);

  $datah = [];

  foreach ($data_akses as $key => $value) {

    array_push($datah, $value->kode);

  }

  // if ($ci->session->userdata("is_admin") || in_array($kode, $ci->session->userdata("can_akses"))) {
  if (session('is_admin') == 1 || in_array($kode, $datah)) {

    return true;

  } else {

    return false;

  }

}
}