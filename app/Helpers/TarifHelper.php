<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class TarifHelper
{
    public static function ruang($noreg)
    {
        $data = DB::select(
            "SELECT rs23.rs1 as noreg,rs23.rs5 as kodekamar,rs24.rs4 as koderuang,rs24.rs3 as kelas,rs23.rs19 as sistembayar,
							CASE rs24.rs3
								WHEN '3' THEN rs30tarif.rs6
								WHEN 'IC' THEN rs30tarif.rs6
								WHEN 'ICC' THEN rs30tarif.rs6
								WHEN 'NICU' THEN rs30tarif.rs6
								WHEN 'IN' THEN rs30tarif.rs6
								WHEN 'ISO' THEN rs30tarif.rs6
								WHEN '2' THEN rs30tarif.rs8
								WHEN '1' THEN rs30tarif.rs10
                                WHEN 'HCU' THEN rs30tarif.rs10
								WHEN 'Utama' THEN rs30tarif.rs12
								WHEN 'VIP' THEN rs30tarif.rs14
								WHEN 'VVIP' THEN rs30tarif.rs16
                                WHEN 'PS' THEN rs30tarif.pss
							END as sarana,CASE rs24.rs3
								WHEN '3' THEN rs30tarif.rs7
								WHEN 'IC' THEN rs30tarif.rs7
								WHEN 'ICC' THEN rs30tarif.rs7
								WHEN 'NICU' THEN rs30tarif.rs7
								WHEN 'IN' THEN rs30tarif.rs7
								WHEN 'ISO' THEN rs30tarif.rs7
								WHEN '2' THEN rs30tarif.rs9
								WHEN '1' THEN rs30tarif.rs11
                                WHEN 'HCU' THEN rs30tarif.rs11
								WHEN 'Utama' THEN rs30tarif.rs13
								WHEN 'VIP' THEN rs30tarif.rs15
								WHEN 'VVIP' THEN rs30tarif.rs17
                                WHEN 'PS' THEN rs30tarif.psp
							END as pelayanan
							from rs23,rs24,rs30tarif where rs24.rs1=rs23.rs5
							and rs30tarif.rs3='K1#' and rs30tarif.rs4 like concat('%',rs24.rs4,'%')
							and rs30tarif.rs5 like concat('%',rs24.rs3,'%') and rs23.rs1='".$noreg."'"
        );

        return $data;
    }
    public static function admin($noreg)
    {
        $data = DB::select(
            "SELECT rs23.rs1 as noreg,
							rs23.rs19 as sistembayar,
							rs24.rs4 as koderuang,
						-- rs23.rs5 as kodekamar,rs24.rs4 as koderuang,rs24.rs3 as kelas,rs23.rs19 as sistembayar,
							-- CASE rs24.rs3
							-- 	WHEN '3' THEN rs30tarif.rs6
							-- 	WHEN 'IC' THEN rs30tarif.rs6
							-- 	WHEN 'ICC' THEN rs30tarif.rs6
							-- 	WHEN 'NICU' THEN rs30tarif.rs6
							-- 	WHEN 'IN' THEN rs30tarif.rs6
							-- 	WHEN 'ISO' THEN rs30tarif.rs6
							-- 	WHEN '2' THEN rs30tarif.rs8
							-- 	WHEN '1' THEN rs30tarif.rs10
							-- 	WHEN 'Utama' THEN rs30tarif.rs12
							-- 	WHEN 'VIP' THEN rs30tarif.rs14
							-- 	WHEN 'VVIP' THEN rs30tarif.rs16
							-- END as sarana,CASE rs24.rs3
							-- 	WHEN '3' THEN rs30tarif.rs7
							-- 	WHEN 'IC' THEN rs30tarif.rs7
							-- 	WHEN 'ICC' THEN rs30tarif.rs7
							-- 	WHEN 'NICU' THEN rs30tarif.rs7
							-- 	WHEN 'IN' THEN rs30tarif.rs7
							-- 	WHEN 'ISO' THEN rs30tarif.rs7
							-- 	WHEN '2' THEN rs30tarif.rs9
							-- 	WHEN '1' THEN rs30tarif.rs11
							-- 	WHEN 'Utama' THEN rs30tarif.rs13
							-- 	WHEN 'VIP' THEN rs30tarif.rs15
							-- 	WHEN 'VVIP' THEN rs30tarif.rs17
							-- END as pelayanan

							rs30tarif.rs6 as sarana,rs30tarif.rs7 as pelayanan

							from rs23,rs24,rs30tarif
							where rs24.rs1=rs23.rs5
							and rs30tarif.rs3='A1#'
							-- and rs30tarif.rs3='K1#' and rs30tarif.rs4 like concat('%',rs24.rs4,'%')
							-- and rs30tarif.rs5 like concat('%',rs24.rs3,'%')
							and rs23.rs1='".$noreg."'"
        );

        return $data;
    }



}
