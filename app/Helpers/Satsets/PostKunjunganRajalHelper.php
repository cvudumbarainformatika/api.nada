<?php

namespace App\Helpers\Satsets;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingLoincHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Models\Pasien;
use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Allergy;
use App\Models\Simrs\Master\MkuSnomed;
use App\Models\Simrs\Master\Msnomed;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isEmpty;

class PostKunjunganRajalHelper
{

    public static function cekKunjungan()
    {
      // $ygTerkirim =0;
      // $arrayKunjungan = self::cekKunjunganRajal();
      // return $arrayKunjungan;
      // return count($arrayKunjungan);
      // return self::rajal($arrayKunjungan[0]);
      // for ($i=0; $i < count($arrayKunjungan) ; $i++) { 
      //   self::rajal($arrayKunjungan[$i]);
      //   $ygTerkirim = $i+1;
      //   // break;
      //   // sleep(5);//menunggu 10 detik
      // }
      // return ['yg terkirim'=>$ygTerkirim, 'jml_kunjungan' => count($arrayKunjungan)];

      $tgl = Carbon::now()->subDays(5)->toDateString();
      return self::rajal($tgl);
    }

    public static function cekKunjunganRajal()
    {
      $tgl = Carbon::now()->subDay()->toDateString();
      // $tgl = Carbon::now()->subDays(1)->toDateString();
      $data = KunjunganPoli::select('rs17.rs1')
      ->with([
        'satset:uuid', 'satset_error:uuid'
      ])
        ->doesntHave('satset')
        ->doesntHave('satset_error')
        ->where('rs17.rs3', 'LIKE', '%' . $tgl . '%')
        ->where('rs17.rs8', '!=', 'POL014')
        ->where('rs17.rs19', '=', '1') // kunjungan selesai
        // ->whereNotNull('satsets.uuid')
        // ->whereNotNull('satset_error_respon.uuid')
        ->orderBy('rs17.rs3', 'desc')
      ->limit(2)
      ->get();
      // $arr = collect($data)->map(function ($x) {
      //   return $x->rs1;
      // });
      
      // return $arr->toArray();
      return $data;
    }

    public static function cobarajal($noreg)
    {
      $bukanPoli = ['POL014','PEN005','PEN004'];

      $data = KunjunganPoli::select(
        'rs17.rs1',
        'rs17.rs9',
        'rs17.rs4',
        'rs17.rs8',
        'rs17.rs1 as noreg',
        'rs17.rs2 as norm',
        'rs17.rs3 as tgl_kunjungan',
        'rs17.rs8 as kodepoli',
        'rs19.rs2 as poli',
        'rs17.rs9 as kodedokter',
        'rs21.rs2 as dokter',
        'rs17.rs14 as kodesistembayar',
        'rs9.rs2 as sistembayar',
        'rs9.groups as groups',
        'rs15.rs2 as nama',
        'rs15.rs49 as nik',
        'rs17.rs19 as status',
        'rs15.satset_uuid as pasien_uuid',
        // 'satsets.uuid as satset',
        // 'satset_error_respon.uuid as satset_error',
        )
        ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
        // ->leftjoin('satsets', 'satsets.uuid', '=', 'rs17.rs1') //satset
        // ->leftjoin('satset_error_respon', 'satset_error_respon.uuid', '=', 'rs17.rs1') //satset error

        ->where('rs17.rs1', $noreg)
        ->whereNotIn('rs17.rs8', $bukanPoli)
        ->where('rs17.rs19', '=', '1') // kunjungan selesai

        ->with([
            'satset:uuid', 'satset_error:uuid',
            'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
            'relmpoli'=>function($q){
              $q->select('rs1','kode_ruang','rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid');
            },
            //   // 1 (mulai waktu tunggu admisi),
            //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
            //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
            //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
            //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
            //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
            //   // 7 (akhir waktu obat selesai dibuat),
            //   // 99 (tidak hadir/batal)
            'taskid' => function ($q) {
                $q->select('noreg', 'taskid', 'waktu', 'created_at')
                    ->orderBy('taskid', 'ASC');
            },
            'diagnosa' => function ($d) {
                $d->select('rs1','rs3','rs4','rs7','rs8');
                $d->with('masterdiagnosa');
            },
            'anamnesis',
            // 'pemeriksaanfisik' => function ($a) {
            //   $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
            //       ->orderBy('id', 'DESC');
            // },
            'pemeriksaanfisik' => function ($a) {
              $a->with(['detailgambars:rs236_id,noreg,norm,tgl,anatomy,ket,user', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                  ->orderBy('id', 'DESC');
            },
            
            'tindakan' => function ($t) {
                $t->select('rs73.rs1','rs73.rs2','rs73.rs3','rs73.rs4','rs73.rs8','rs73.rs9','rs30.rs2 as keterangan','rs30.rs1 as kode');
                $t->leftjoin('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                    ->with([
                    'maapingprocedure'=> function($mp){
                        $mp->select('prosedur_mapping.kdMaster','prosedur_mapping.icd9','prosedur_master.prosedur')
                        ->leftjoin('prosedur_master', 'prosedur_master.kd_prosedur', '=', 'prosedur_mapping.icd9');
                        ;
                    },
                    // 'maapingprocedure:kdMaster,icd9','maapingprocedure.prosedur:kd_prosedur,prosedure',
                    'maapingsnowmed:kdMaster,kdSnowmed,display',
                    'petugas:nama,kdpegsimrs,satset_uuid'
                    ])
                ->groupBy('rs73.rs4')
                ->orderBy('id', 'DESC');
            },

            'planning' => function ($p) {
                $p->select('rs1','rs2','rs3','rs4','rs5','tgl','user','flag');
                $p->with([
                    'masterpoli:rs1,rs7,rs6,panggil_antrian,displaykode,kode_ruang',
                    'rekomdpjp',
                    'transrujukan',
                    // 'listkonsul:noreg_lama,norm,tgl_kunjungan,tgl_rencana_konsul,kdpoli_asal,kdpoli_tujuan,kddokter_asal,flag',
                    'listkonsul' => function($lk) {
                    $lk->select('noreg_lama','norm','tgl_kunjungan','tgl_rencana_konsul','kdpoli_asal','kdpoli_tujuan','kddokter_asal','flag','rs17.rs9 as kdDokterKonsul','rs19.kode_ruang')
                        ->leftJoin('rs17', 'rs17.rs4', '=', 'listkonsulanpoli.noreg_lama')
                        ->leftJoin('rs19', 'rs19.rs1', '=', 'listkonsulanpoli.kdpoli_tujuan')
                        ->with(['dokterkonsul'=> function($dk) {
                            $dk->select('kdpegsimrs','nama','satset_uuid') //kdpegsimrs,nama,satset_uuid
                            ->where('aktif', '=', 'AKTIF');
                        },
                        
                        'lokasikonsul:kode,uraian,satset_uuid']);
                    },
                    'spri:noreg,norm,kodeDokter,tglRencanaKontrol,noSuratKontrol,nama,kelamin,user_id',
                    'spri.petugas:nama,kdpegsimrs,satset_uuid',
                    'ranap:rs1,rs2,rs3,rs4,rs5,rs6,rs7,groups,status,hiddens,groups_nama,jenis',
                    'kontrol' => function ($k) {
                    $k->select('noreg','norm','kodeDokter as kdDokterKontrol','poliKontrol','tglRencanaKontrol','created_at','rs19.kode_ruang')
                    ->leftJoin('rs19', 'rs19.rs6', '=', 'bpjs_surat_kontrol.poliKontrol')
                    ->with(['dokterkontrol'=> function($dk) {
                        $dk->select('kddpjp','nama','satset_uuid') // kddpjp,nama,satset_uuid
                        ->where('aktif', '=', 'AKTIF');
                    },
                    'lokasikontrol:kode,uraian,satset_uuid']);
                    },
                    'operasi',
                ])->orderBy('id', 'DESC');
            },

            'diagnosakeperawatan'=> function ($d) {
                $d->with('petugas:id,nama,satset_uuid','intervensi.masterintervensi');
            },

            'apotek' => function ($apot) {
                $apot->whereIn('flag', ['3', '4'])->with([
                    // 'rincian.mobat:kd_obat,nama_obat',
                    // 'rincianracik.mobat:kd_obat,nama_obat',
                    'rincian' => function ($ri) {
                        $ri->select(
                            'resep_keluar_r.id',
                            'resep_keluar_r.kdobat',
                            'resep_keluar_r.noresep',
                            'resep_keluar_r.jumlah',
                            'resep_keluar_r.aturan', // signa
                            'resep_keluar_r.konsumsi', // signa
                            'resep_keluar_r.keterangan', // signa
                            'retur_penjualan_r.jumlah_retur',
                            'signa.jumlah as konsumsi_perhari',
                            DB::raw('
                            CASE
                            WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
                            ELSE resep_keluar_r.jumlah
                            END as qty
                            ') // iki jumlah obat sing non racikan mas..
                        )
                            ->leftJoin('retur_penjualan_r', function ($jo) {
                                $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                                    ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
                            })
                            ->leftJoin('signa', 'signa.signa', '=', 'resep_keluar_r.aturan')
                            ->with([
                                'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                                // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                                // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                            ]);
                    },
                    'rincianracik' => function ($ri) {
                        $ri->select(
                            'resep_keluar_racikan_r.kdobat',
                            'resep_keluar_racikan_r.noresep',
                            'resep_keluar_racikan_r.jumlah',
                            'resep_keluar_racikan_r.jumlahdibutuhkan as qty', // MedicationRequest.dispenseRequest.quantity dan non-dtd -> Medication.ingredient.strength.denominator
                            'resep_keluar_racikan_r.tiperacikan', // dtd / non-dtd
                            'resep_permintaan_keluar_racikan.dosismaksimum', // dtd -> Medication.ingredient.strength.numerator
                            'resep_permintaan_keluar_racikan.aturan', // signa
                        )
                            ->leftJoin('resep_permintaan_keluar_racikan', function ($jo) {
                                $jo->on('resep_permintaan_keluar_racikan.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                                    ->on('resep_permintaan_keluar_racikan.noresep', '=', 'resep_keluar_racikan_r.noresep');
                            })
                            ->with([
                                'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                                // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                                // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                            ]);
                    },
                    'petugas:id,nik,nama,satset_uuid',

                ])
                ->orderBy('id', 'DESC');
            },

            'neonatusmedis',
            'neonatuskeperawatan',
            'pediatri',
            'diet',
            'telaahresep'=>function($t){
                $t->with('petugas:id,nama,satset_uuid');
            },
            'laborats' => function ($t) {
                $t->with([
                    'details' => function ($d) {
                        $d->with(['pemeriksaanlab'=>function($p){
                            // $p->orderBy('id', 'ASC');
                            $p->select('rs49.*',
                            'rs49_spesimen.jenis_spesimen',
                            'rs49_spesimen.jumlah_spesimen',
                            'rs49_spesimen.volume_spesimen_klinis',
                            'rs49_spesimen.cara_pengambilan_spesimen',
                            'rs49_spesimen.cairan_fiksasi',
                            'rs49_spesimen.volume_cairan_fiksasi',
                            )->with('loinclab')
                            ->leftJoin('rs49_spesimen', 'rs49.rs1', '=', 'rs49_spesimen.rs1')
                            ->orderBy('id', 'ASC');
                        }
                        ])->orderBy('rs4', 'ASC');
                    }
                    
                ])
                ->orderBy('id', 'DESC');
            },
          ])


        //   ->doesntHave('satset')
        //   ->doesntHave('satset_error')

      

        ->orderby('rs17.rs3', 'ASC')
        ->first();

        // return $data;
      return self::kirimKunjungan($data);
    }

    public static function rajal($tgl)
    {
      $bukanPoli = ['POL014','PEN005','PEN004'];

      $data = KunjunganPoli::select(
        'rs17.rs1',
        'rs17.rs9',
        'rs17.rs4',
        'rs17.rs8',
        'rs17.rs1 as noreg',
        'rs17.rs2 as norm',
        'rs17.rs3 as tgl_kunjungan',
        'rs17.rs8 as kodepoli',
        'rs19.rs2 as poli',
        'rs17.rs9 as kodedokter',
        'rs21.rs2 as dokter',
        'rs17.rs14 as kodesistembayar',
        'rs9.rs2 as sistembayar',
        'rs9.groups as groups',
        'rs15.rs2 as nama',
        'rs15.rs49 as nik',
        'rs17.rs19 as status',
        'rs15.satset_uuid as pasien_uuid',
        // 'satsets.uuid as satset',
        // 'satset_error_respon.uuid as satset_error',
        )
        ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
        // ->leftjoin('satsets', 'satsets.uuid', '=', 'rs17.rs1') //satset
        // ->leftjoin('satset_error_respon', 'satset_error_respon.uuid', '=', 'rs17.rs1') //satset error

        // ->where('rs17.rs1', $noreg)
        ->whereNotIn('rs17.rs8', $bukanPoli)
        ->where('rs17.rs19', '=', '1') // kunjungan selesai
        ->where('rs17.rs3', 'LIKE', '%' . $tgl . '%')
        
        // ->whereBetween('rs17.rs3', [$tgl, $tglx])
        // ->where('rs17.rs8', $user->kdruangansim ?? '')
        // ->where('rs17.rs3', 'LIKE', '%' . $kemarin . '%')
        // ->where('rs17.rs8', '!=', 'POL014')
        // ->where('rs17.rs19', '=', '1') // kunjungan selesai

        // ->where('rs19.rs5', '=', '1')
        // ->where('rs19.rs4', '=', 'Poliklinik')
        // ->whereNull('satsets.uuid')

        // ->where(function ($query) {
        //     $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%') //pasien nama
        //         ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%') //pasien
        //         ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
        //         ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
        //         ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
        //         ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
        //         // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
        //         ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
        // })

        ->with([
            'satset:uuid', 'satset_error:uuid',
            'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
            'relmpoli'=>function($q){
              $q->select('rs1','kode_ruang','rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid');
            },
            //   // 1 (mulai waktu tunggu admisi),
            //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
            //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
            //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
            //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
            //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
            //   // 7 (akhir waktu obat selesai dibuat),
            //   // 99 (tidak hadir/batal)
            'taskid' => function ($q) {
                $q->select('noreg', 'taskid', 'waktu', 'created_at')
                    ->orderBy('taskid', 'ASC');
            },
            'diagnosa' => function ($d) {
                $d->select('rs1','rs3','rs4','rs7','rs8');
                $d->with('masterdiagnosa');
            },
            'anamnesis',
            'pemeriksaanfisik' => function ($a) {
              $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                  ->orderBy('id', 'DESC');
            },
            
            'tindakan' => function ($t) {
                $t->select('rs73.rs1','rs73.rs2','rs73.rs3','rs73.rs4','rs73.rs8','rs73.rs9','rs30.rs2 as keterangan','rs30.rs1 as kode');
                $t->leftjoin('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                    ->with([
                    'maapingprocedure'=> function($mp){
                        $mp->select('prosedur_mapping.kdMaster','prosedur_mapping.icd9','prosedur_master.prosedur')
                        ->leftjoin('prosedur_master', 'prosedur_master.kd_prosedur', '=', 'prosedur_mapping.icd9');
                        ;
                    },
                    // 'maapingprocedure:kdMaster,icd9','maapingprocedure.prosedur:kd_prosedur,prosedure',
                    'maapingsnowmed:kdMaster,kdSnowmed,display',
                    'petugas:nama,kdpegsimrs,satset_uuid'
                    ])
                ->groupBy('rs73.rs4')
                ->orderBy('id', 'DESC');
            },

            

            'planning' => function ($p) {
                $p->select('rs1','rs2','rs3','rs4','rs5','tgl','user','flag');
                $p->with([
                    'masterpoli:rs1,rs7,rs6,panggil_antrian,displaykode,kode_ruang',
                    'rekomdpjp',
                    'transrujukan',
                    // 'listkonsul:noreg_lama,norm,tgl_kunjungan,tgl_rencana_konsul,kdpoli_asal,kdpoli_tujuan,kddokter_asal,flag',
                    'listkonsul' => function($lk) {
                    $lk->select('noreg_lama','norm','tgl_kunjungan','tgl_rencana_konsul','kdpoli_asal','kdpoli_tujuan','kddokter_asal','flag','rs17.rs9 as kdDokterKonsul','rs19.kode_ruang')
                        ->leftJoin('rs17', 'rs17.rs4', '=', 'listkonsulanpoli.noreg_lama')
                        ->leftJoin('rs19', 'rs19.rs1', '=', 'listkonsulanpoli.kdpoli_tujuan')
                        ->with(['dokterkonsul'=> function($dk) {
                            $dk->select('kdpegsimrs','nama','satset_uuid') //kdpegsimrs,nama,satset_uuid
                            ->where('aktif', '=', 'AKTIF');
                        },
                        
                        'lokasikonsul:kode,uraian,satset_uuid']);
                    },
                    'spri:noreg,norm,kodeDokter,tglRencanaKontrol,noSuratKontrol,nama,kelamin,user_id',
                    'spri.petugas:nama,kdpegsimrs,satset_uuid',
                    'ranap:rs1,rs2,rs3,rs4,rs5,rs6,rs7,groups,status,hiddens,groups_nama,jenis',
                    'kontrol' => function ($k) {
                    $k->select('noreg','norm','kodeDokter as kdDokterKontrol','poliKontrol','tglRencanaKontrol','created_at','rs19.kode_ruang')
                    ->leftJoin('rs19', 'rs19.rs6', '=', 'bpjs_surat_kontrol.poliKontrol')
                    ->with(['dokterkontrol'=> function($dk) {
                        $dk->select('kddpjp','nama','satset_uuid') // kddpjp,nama,satset_uuid
                        ->where('aktif', '=', 'AKTIF');
                    },
                    'lokasikontrol:kode,uraian,satset_uuid']);
                    },
                    'operasi',
                ])->orderBy('id', 'DESC');
            },

            'diagnosakeperawatan'=> function ($d) {
                $d->with('petugas:id,nama,satset_uuid','intervensi.masterintervensi');
            },

            'apotek' => function ($apot) {
                $apot->whereIn('flag', ['3', '4'])->with([
                    // 'rincian.mobat:kd_obat,nama_obat',
                    // 'rincianracik.mobat:kd_obat,nama_obat',
                    'rincian' => function ($ri) {
                        $ri->select(
                            'resep_keluar_r.kdobat',
                            'resep_keluar_r.noresep',
                            'resep_keluar_r.jumlah',
                            'resep_keluar_r.aturan', // signa
                            'resep_keluar_r.konsumsi', // signa
                            'resep_keluar_r.keterangan', // signa
                            'retur_penjualan_r.jumlah_retur',
                            'signa.jumlah as konsumsi_perhari',
                            DB::raw('
                            CASE
                            WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
                            ELSE resep_keluar_r.jumlah
                            END as qty
                            ') // iki jumlah obat sing non racikan mas..
                        )
                            ->leftJoin('retur_penjualan_r', function ($jo) {
                                $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                                    ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
                            })
                            ->leftJoin('signa', 'signa.signa', '=', 'resep_keluar_r.aturan')
                            ->with([
                                'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                                // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                                // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                            ]);
                    },
                    'rincianracik' => function ($ri) {
                        $ri->select(
                            'resep_keluar_racikan_r.kdobat',
                            'resep_keluar_racikan_r.noresep',
                            'resep_keluar_racikan_r.jumlah',
                            'resep_keluar_racikan_r.jumlahdibutuhkan as qty', // MedicationRequest.dispenseRequest.quantity dan non-dtd -> Medication.ingredient.strength.denominator
                            'resep_keluar_racikan_r.tiperacikan', // dtd / non-dtd
                            'resep_permintaan_keluar_racikan.dosismaksimum', // dtd -> Medication.ingredient.strength.numerator
                            'resep_permintaan_keluar_racikan.aturan', // signa
                        )
                            ->leftJoin('resep_permintaan_keluar_racikan', function ($jo) {
                                $jo->on('resep_permintaan_keluar_racikan.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                                    ->on('resep_permintaan_keluar_racikan.noresep', '=', 'resep_keluar_racikan_r.noresep');
                            })
                            ->with([
                                'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                                // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                                // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                            ]);
                    },
                    'petugas:id,nik,nama,satset_uuid',

                ])
                ->orderBy('id', 'DESC');
            },

            'neonatusmedis',
            'neonatuskeperawatan',
            'pediatri',
            'diet',
            'telaahresep'=>function($t){
                $t->with('petugas:id,nama,satset_uuid');
            },
            'laborats' => function ($t) {
                $t->with([
                    'details' => function ($d) {
                        $d->with(['pemeriksaanlab'=>function($p){
                            // $p->orderBy('id', 'ASC');
                            $p->select('rs49.*',
                            'rs49_spesimen.jenis_spesimen',
                            'rs49_spesimen.jumlah_spesimen',
                            'rs49_spesimen.volume_spesimen_klinis',
                            'rs49_spesimen.cara_pengambilan_spesimen',
                            'rs49_spesimen.cairan_fiksasi',
                            'rs49_spesimen.volume_cairan_fiksasi',
                            )->with('loinclab')
                            ->leftJoin('rs49_spesimen', 'rs49.rs1', '=', 'rs49_spesimen.rs1')
                            ->orderBy('id', 'ASC');
                        }
                        ])->orderBy('rs4', 'ASC');
                    }
                    
                ])
                ->orderBy('id', 'DESC');
            },
          ])


          ->doesntHave('satset')
          ->doesntHave('satset_error')

      

        ->orderby('rs17.rs3', 'ASC')
        // ->limit(1)
        // ->get();
        ->first();

        // return $data;
      return self::kirimKunjungan($data);
    }

    public static function kirimKunjungan($data)
    {
        // return $data;
      $pasien_uuid = $data->pasien_uuid;
      $practitioner_uuid = $data->datasimpeg ? $data->datasimpeg['satset_uuid'] : null;
        //   $apoteker_uuid = $data->apotek ? ($data->apotek['petugas'] ? $data->apotek['petugas']['satset_uuid'] : null): null;

      if (!$pasien_uuid) {
        $getPasienFromSatset = self::getPasienByNikSatset($data);
        $pasien_uuid = $getPasienFromSatset['data']['uuid'];
      }

      if (!$practitioner_uuid) {
        $getFromSatset = self::getPractitionerFromSatset($data);
        $practitioner_uuid = $getFromSatset['data']['uuid'];
      }



      $send = self::form($data, $pasien_uuid, $practitioner_uuid);
      if ($send['message'] === 'success') {
        $token = AuthSatsetHelper::accessToken();
        $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg);
      }
      return $send;
    }

    public static function getPasienByNikSatset($pasien)
    {
        // return $request->all();
        $nik = $pasien->nik;
        $norm = $pasien->norm;
        // get data ke satset
        $token = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        } else {
           SatsetErrorRespon::create([
               'uuid' => $pasien->noreg,
               'response' => $send
           ]);
        }
        return $send;
    }

    public static function getPractitionerFromSatset($pasien)
    {
      $nik = $pasien->datasimpeg ? $pasien->datasimpeg['nik'] : null;
      $token = AuthSatsetHelper::accessToken();
      $params = '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

      $send = BridgingSatsetHelper::get_data($token, $params);

      $data = Pegawai::where('nik', $nik)->where('aktif','AKTIF')->first();

      if ($send['message'] === 'success') {
          $data->satset_uuid = $send['data']['uuid'];
          $data->save();
      } else {
          SatsetErrorRespon::create([
              'uuid' => $pasien->noreg,
              'response' => $send
          ]);
      }
      return $send;
    }
    
    public static function generateUuid()
    {
        return (string) Str::uuid();
    }

    public static function form($request, $pasien_uuid, $practitioner_uuid)
    {
        $send = [
            'message' =>  'failed',
            'data' => null
        ];

        $encounter = self::generateUuid();

        $practitioner = $practitioner_uuid;

        $taskid = collect($request->taskid);
        if (count($taskid) === 0) {
            $send['data'] = 'data taskid dari request kosong';
            return $send;
        }

        $task3 = $taskid->filter(function ($item) {
          return $item['taskid'] === '3';
        })->first();
        $task4 = $taskid->filter(function ($item) {
            return $item['taskid'] === '4';
        })->first();
        $task5 = $taskid->filter(function ($item) {
            return $item['taskid'] === '5';
        })->first();

        if (!$task3 || !$task5) {
            
            SatsetErrorRespon::create([
                'uuid' => $request->noreg,
                'response' => 'TASK iD Tdk lengkap',
            ]);

            $send['data'] = 'TASK iD Tdk lengkap';
            return $send;
        }

        $antri = Carbon::parse($task3['created_at'])->toIso8601String();

        $start = isset($task4['created_at']) ? Carbon::parse($task4['created_at'])->toIso8601String() : Carbon::parse($task3['created_at'])->addMinutes(3)->toIso8601String();
        $end = Carbon::parse($task5['created_at'])->toIso8601String();

        setlocale(LC_ALL, 'IND');
        $dt = Carbon::parse($request->tgl_kunjungan)->locale('id');
        $dt->settings(['formatFunction' => 'translatedFormat']);
        $tgl_kunjungan = $dt->format('l, j F Y');
        // $tgl_kunjungan = $dt->format('l, j F Y ; h:i a');

        $rajal_org = '4b8fb632-6435-4fc1-8ea0-7aacc39974d6';
        $organization_id = BridgingSatsetHelper::organization_id();


        $specimenSnomeds =  Msnomed::whereNotNull('spesimen')->get();


        // DIAGNOSA

        $diagnosa = [];
        $refference = [];
        foreach ($request->diagnosa as $key => $value) {
            $uuid = self::generateUuid();
            $data = [
                "condition" => [
                    "reference" => "urn:uuid:$uuid",
                    "display" => $value['masterdiagnosa']['rs4']
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => "DD",
                            "display" => "Discharge diagnosis"
                        ]
                    ]
                ],
                "rank" => $key + 1
            ];

            $diagnosa[] = $data;

            $refference[] = [
                "reference" => "$uuid",
                'code' => $value['masterdiagnosa']['rs1'],
                "jenis" => $value['rs4'],
                "display" => $value['masterdiagnosa']['rs4'],
                "displayInd" => $value['masterdiagnosa']['rs3'],
                "rank" => $key + 1
            ];
        }


        // return $antri;
        #Bundle #1

        $relmasterRuang = $request->relmpoli['ruang'];
        $ruangId = !$relmasterRuang ? '-': $relmasterRuang['satset_uuid'] ?? '-';
        $ruang = !$relmasterRuang ? '-': $relmasterRuang['ruang'] ?? '-';
        $lantai = !$relmasterRuang ? '-': $relmasterRuang['lantai'] ?? '-';
        $gedung = !$relmasterRuang ? '-': $relmasterRuang['gedung'] ?? '-';



        $anamnesis = self::anamnesis($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $observation = self::observation($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $carePlan = self::carePlan($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $procedure = self::procedure($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $plann = self::planning($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $refference);
        $alergyIntoleran = self::allergyIntoleran($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        // $tebus = self::tebusObat($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $screeningGizi = self::screeningGizi($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $apotek = self::apotek($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $diet = self::diet($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $laborats = self::laborats($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $specimenSnomeds);
        $anamnesis = self::anamnesis($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $telaah = self::telaah($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $riwayatPengobatan = self::riwayatPengobatan($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);

        // return $riwayatPengobatan;

        $body =
        [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => [
                // ENCOUNTER & CONDITION
                [
                    "fullUrl" => "urn:uuid:$encounter",
                    "resource" => [
                        "resourceType" => "Encounter",
                        "status" => "finished",
                        "class" => [
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                            "code" => "AMB",
                            "display" => "ambulatory"
                        ],
                        "subject" => [
                            "reference" => "Patient/$pasien_uuid",
                            "display" => $request->nama
                        ],
                        "participant" => [
                            [
                                "type" => [
                                    [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                                "code" => "ATND",
                                                "display" => "attender"
                                            ]
                                        ]
                                    ]
                                ],
                                "individual" => [
                                    "reference" => "Practitioner/$practitioner",
                                    "display" => $request->datasimpeg['nama']
                                ]
                            ]
                        ],
                        "period" => [
                            "start" => $antri,
                            "end" => $end
                        ],
                        "location" => [
                            [
                                "location" => [
                                    "reference" => "Location/" . $ruangId,
                                    "display" => "Ruang " . $ruang. " " . $relmasterRuang['panggil_antrian'] . ", RSUD Mohamad Saleh, Lantai " . $lantai . ", Gedung " . $gedung
                                    // "display" => $request['relmpoli']['ruang']['gedung']
                                ]
                            ]
                        ],
                        "diagnosis" => $diagnosa,
                        "statusHistory" => [
                            [
                                "status" => "arrived",
                                "period" => [
                                    "start" => $antri,
                                    "end" => $start
                                ]
                            ],
                            [
                                "status" => "in-progress",
                                "period" => [
                                    "start" => $start,
                                    "end" => $end
                                ]
                            ],
                            [
                                "status" => "finished",
                                "period" => [
                                    "start" => $end,
                                    "end" => $end
                                ]
                            ]
                        ],
                        "serviceProvider" => [
                            // "reference" => "Organization/$organization_id"
                            "reference" => "Organization/$organization_id"
                        ],

                        // gak yakin
                        "identifier" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/encounter/$organization_id",
                                "value" => $pasien_uuid
                                // "value" => "P20240001"
                            ]
                        ]
                    ],
                    "request" => [
                        "method" => "POST",
                        "url" => "Encounter"
                    ]
                ],

                // OBSERVATION
                $observation['nadi'],
                $observation['pernapasan'],
                $observation['sistole'],
                $observation['diastole'],
                $observation['suhu'],
                $observation['kesadaran'],
                $observation['psikologis'],


                // CARE PLAN Push di bawah karena banyak & sdh dinamis

                // PROCEDURE / TINDAKAN PUSH DI BAWAH KARENA BANYAK & SDH DINAMIS
                // PLANNING / SERVICE_REQUEST PUSH DI BAWAH KARENA BANYAK & SDH DINAMIS
            ]
        ];



        //  PUSH CONDITION
        foreach ($request->diagnosa as $key => $value) {
            $cond =
                [
                    // "fullUrl" => "urn:uuid:ba5a7dec-023f-45e1-adb9-1b9d71737a5f",
                    "fullUrl" => $diagnosa[$key]['condition']['reference'],
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active"
                                ]
                            ]
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "encounter-diagnosis",
                                        "display" => "Encounter Diagnosis"
                                    ]
                                ]
                            ]
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => $value['rs3'],
                                    "display" => $value['masterdiagnosa']['rs4']
                                ]
                            ]
                        ],
                        "subject" => [
                            "reference" => "Patient/$pasien_uuid",
                            "display" => $request->nama
                        ],
                        "encounter" => [
                            "reference" => "urn:uuid:$encounter",
                            "display" => "Kunjungan $request->nama di hari $tgl_kunjungan"
                        ]
                    ],
                    "request" => [
                        "method" => "POST",
                        "url" => "Condition"
                    ]
                ];

            array_push($body['entry'], $cond);
        }

        // PUSH ANAMESIS
        if ($anamnesis['keluhanUtama'] !== null) array_push($body['entry'], $anamnesis['keluhanUtama']);

        // PUSH careplan
        for ($i=0; $i < count($carePlan) ; $i++) { 
            array_push($body['entry'], $carePlan[$i]);
        }

        // PUSH PROCEDURE
        for ($i=0; $i < count($procedure) ; $i++) { 
            // array_push($body['entry'], $procedure[$i]);
            if ($procedure[$i]!== null) array_push($body['entry'], $procedure[$i]);
        }

        // PUSH PLANNING
        if ($plann['spri'] !== null) array_push($body['entry'], $plann['spri']);
        if ($plann['konsul'] !== null) array_push($body['entry'], $plann['konsul']);
        if ($plann['kontrol'] !== null) array_push($body['entry'], $plann['kontrol']);


        // PUSH DIET
        if ($diet !== null) array_push($body['entry'], $diet);

        // PUSH ALLERGY INTOLERANCE
        if ($alergyIntoleran !== null) array_push($body['entry'], $alergyIntoleran);

        // PUSH MEDICATION
        // for ($i=0; $i < count($apotek['nonracikan']) ; $i++) { 
        //     array_push($body['entry'], $apotek['nonracikan'][$i][0]); // Medication
        //     array_push($body['entry'], $apotek['nonracikan'][$i][1]); // MedicationRequest
        //     array_push($body['entry'], $apotek['nonracikan'][$i][2]); // MedicationForDispense
        //     array_push($body['entry'], $apotek['nonracikan'][$i][3]); // MedicationDispense
        // }
        for ($i=0; $i < count($apotek['nonracikan']) ; $i++) { 
            if($apotek['nonracikan'][$i]['medication']) array_push($body['entry'], $apotek['nonracikan'][$i]['medication']); // Medication
            if($apotek['nonracikan'][$i]['medication_request']) array_push($body['entry'], $apotek['nonracikan'][$i]['medication_request']); // MedicationRequest
            if($apotek['nonracikan'][$i]['medicationD']) array_push($body['entry'], $apotek['nonracikan'][$i]['medicationD']); // Medication
            if($apotek['nonracikan'][$i]['medication_dispense']) array_push($body['entry'], $apotek['nonracikan'][$i]['medication_dispense']); // MedicationRequest
            
        }
        // for ($i=0; $i < count($apotek['nonracikan']) ; $i++) { 
        //     if($apotek['nonracikan'][$i]['medicationD']) array_push($body['entry'], $apotek['nonracikan'][$i]['medicationD']); // Medication
        //     if($apotek['nonracikan'][$i]['medication_dispense']) array_push($body['entry'], $apotek['nonracikan'][$i]['medication_dispense']); // MedicationRequest
        // }

        // PUSH LABORAT
        for ($i=0; $i < count($laborats) ; $i++) { 
            $serviceRequest = $laborats[$i]['serviceRequests'];
            $hasil = $laborats[$i]['hasil'];
            $spesimen = $laborats[$i]['spesimen'];
            $diagnosticReport = $laborats[$i]['diagnosticReport'];
            // array_push($body['entry'], $laborats[$i]);
            if ($serviceRequest !== null) array_push($body['entry'], $serviceRequest);
            if ($hasil !== null) array_push($body['entry'], $hasil);
            if ($spesimen !== null) array_push($body['entry'], $spesimen);
            if ($diagnosticReport !== null) array_push($body['entry'], $diagnosticReport);
        }

        // PUSH PROGNOSIS
        if ($plann['prognosis'] !== null) array_push($body['entry'], $plann['prognosis']);

        // PUSH QUESTIONER_RESPONSE
        if ($telaah !== null) array_push($body['entry'], $telaah);


        // PUSH MEDICATION STATEMENT
        if ($riwayatPengobatan !== null){
            for ($i=0; $i < count($riwayatPengobatan) ; $i++) { 
                array_push($body['entry'], $riwayatPengobatan[$i]);
            }
        }
        

        $send['message'] = 'success';
        $send['data'] = $body;

        return $send;

        
    }

    public function anamnesis($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        $data = $request->anamnesis[0];
        $keluhanUtama = $data['rs4'];
        // return $keluhanUtama;

        // $q = preg_replace('/[^a-z\d]+/i', ' ', $keluhanUtama);
        // $q = preg_replace('/\s+/', ' ', $q);
        // $q = trim($q);
        $q= strip_tags($keluhanUtama);

        $cari = DB::connection('mysql')->table('m_ku_snomed')
                ->select('*')
                ->whereRaw("MATCH (keterangan) AGAINST (? IN BOOLEAN MODE)", ["*".$q."*"])
                ->orderByRaw("MATCH(keterangan) AGAINST(?) DESC", ["*".$q."*"])
                ->limit(3)
                ->get();
        
        // return $cari[0];
        $KU = null;
       if (count($cari) > 0) {
            $KU = 
            [
                "fullUrl" => "urn:uuid:" . self::generateUuid(),
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "problem-list-item",
                                    "display" => "Problem List Item",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => $cari[0]->codesystem,
                                "code" => (string) $cari[0]->code,
                                "display" => $cari[0]->display
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                    "encounter" => ["reference" => "Encounter/".$encounter],
                    // "onsetDateTime" => "2023-02-02T00:00:00+00:00",
                    // "recordedDate" => "2023-08-31T01:00:00+00:00",
                    "recorder" => ["reference" => "Practitioner/".$practitioner_uuid, "display" => $nama_practitioner],
                    "note" => [["text" => $keluhanUtama]],
                    ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ];
       }
        return [
            'keluhanUtama' => $KU,
        ];
    }

    static function observation($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {

      // $practitioner_uuid = $request->datasimpeg ? $request->datasimpeg['satset_uuid']: '-';
      $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
      $uuid = self::generateUuid();


      $nadi = count($request->pemeriksaanfisik) > 0 ? (int)$request->pemeriksaanfisik[0]['rs4']: 0;
      $pernapasan = count($request->pemeriksaanfisik) > 0 ? (int)$request->pemeriksaanfisik[0]['pernapasan']: 0;
      $sistole = count($request->pemeriksaanfisik) > 0 ? (int)$request->pemeriksaanfisik[0]['sistole']: 0;
      $diastole = count($request->pemeriksaanfisik) > 0 ? (int)$request->pemeriksaanfisik[0]['diastole']: 0;
      $suhu = count($request->pemeriksaanfisik) > 0 ? (int)$request->pemeriksaanfisik[0]['suhutubuh']: 0;
      
      $formNadi = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:$uuid",
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "8867-4",
                        "display" => "Heart rate",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
            "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueQuantity" => [
                "value" => $nadi,
                "unit" => "{beats}/min",
                "system" => "http://unitsofmeasure.org",
                "code" => "{beats}/min",
            ],
        ],
        "request" => ["method" => "POST", "url" => "Observation"],
        ];



      


      $formPernapasan = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:".self::generateUuid(),
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "9279-1",
                        "display" => "Respiratory rate",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
            "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueQuantity" => [
                "value" => $pernapasan,
                "unit"=> "breaths/minute",
                "system"=> "http://unitsofmeasure.org",
                "code"=> "/min"
            ],
          ],
          "request" => ["method" => "POST", "url" => "Observation"],
        ];

      
      
      
      
        $form = [
        'nadi' => $formNadi,
        'kesadaran' => $formPernapasan
      ];


      $formSistole = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:".self::generateUuid(),
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "8480-6",
                        "display" => "Systolic blood pressure",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
            "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueQuantity" => [
                "value" => $sistole,
                "unit" => "mm[Hg]",
                "system" => "http://unitsofmeasure.org",
                "code" => "mm[Hg]",
            ],
          ],
          "request" => ["method" => "POST", "url" => "Observation"],
      ];


      $formDiastole = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:".self::generateUuid(),
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "8462-4",
                        "display" => "Diastolic blood pressure",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
            "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueQuantity" => [
                "value" => $diastole,
                "unit" => "mm[Hg]",
                "system" => "http://unitsofmeasure.org",
                "code" => "mm[Hg]",
            ],
          ],
          "request" => ["method" => "POST", "url" => "Observation"],
      ];

      $formSuhu = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:".self::generateUuid(),
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "8310-5",
                        "display" => "Body temperature",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
            "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueQuantity" => [
                "value" => $suhu,
                "unit"=> "C",
                "system"=> "http://unitsofmeasure.org",
                "code"=> "Cel"
            ],
          ],
          "request" => ["method" => "POST", "url" => "Observation"],
      ];

      $skortingkatKesadaran = count($request->pemeriksaanfisik) ? $request->pemeriksaanfisik[0]['tingkatkesadaran']: 0;

      $snowmedTingkatKesadaran = [
        "kode" => "248234008",
        'display' => "Mentally alert",
        'ind' => 'Sadar Baik/Alert'
      ];

      switch ($skortingkatKesadaran) {
        case 0:
            $snowmedTingkatKesadaran = [
                "kode" => "248234008",
                'display' => "Mentally alert",
                'ind' => 'Sadar Baik/Alert'
              ];
          break;
        case 1:
        //   $tingkatKesadaran = "Berespon denga kata-kata / Voice";
          $snowmedTingkatKesadaran = [
            "kode" => "300202002",
            'display' => "Response to voice",
            'ind' => 'Berespon denga kata-kata / Voice'
          ];
          break;
        case 2:
        //   $tingkatKesadaran = "Hanya berespons jika dirangsang nyeri / Pain";
          $snowmedTingkatKesadaran = [
            "kode" => "450847001",
            'display' => "Responds to pain",
            'ind' => 'Hanya berespons jika dirangsang nyeri / Pain'
          ];

          break;
        case 3:
            $snowmedTingkatKesadaran = [
                "kode" => "422768004",
                'display' => "Unresponsive",
                'ind' => 'Pasien tidak sadar/unresponsive'
              ];
          break;
        case 4:
            $snowmedTingkatKesadaran = [
                "kode" => "130987000",
                'display' => "Acute confusion",
                'ind' => 'Gelisah atau bingung'
              ];
          break;
        case 5:
            $snowmedTingkatKesadaran = [
                "kode" => "2776000",
                'display' => "Delirium",
                'ind' => 'Acute Confusional States'
              ];
          break;
        default:
        $snowmedTingkatKesadaran = [
            "kode" => "248234008",
            'display' => "Mentally alert",
            'ind' => 'Sadar Baik/Alert'
          ];
      }


      $formKesadaran = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:".self::generateUuid(),
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "67775-7",
                        "display" => "Level of responsiveness",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
            "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueCodeableConcept" => [
                "coding" => [
                    [
                        "system" => "http://snomed.info/sct",
                        "code" => $snowmedTingkatKesadaran['kode'],
                        "display" => $snowmedTingkatKesadaran['display'],
                    ],
                ],
            ],
          ],
          "request" => ["method" => "POST", "url" => "Observation"],
      ];

      $statusPsikologis = count($request->pemeriksaanfisik) ? $request->pemeriksaanfisik[0]['statuspsikologis']: 'Tidak ada kelainan';
    
        $psikologis = [
            "kode" => "17326005",
            'display' => "Well in self",
            'ind' => 'Tidak ada kelainan'
        ];

        switch ($statusPsikologis) {
            case 'Tidak ada kelainan':
                $psikologis = [
                    "kode" => "17326005",
                    'display' => "Well in self",
                    'ind' => 'Tidak ada kelainan'
                ];
            break;
            case 'Cemas':
                $psikologis = [
                    "kode" => "48694002",
                    'display' => "Feeling anxious",
                    'ind' => 'Cemas'
                ];
            break;
            case 'Takut':
                $psikologis = [
                    "kode" => "1402001",
                    'display' => "Afraid",
                    'ind' => 'Takut'
                ];

            break;
            case 'Marah':
                $psikologis = [
                    "kode" => "75408008",
                    'display' => "Feeling angry",
                    'ind' => 'Marah'
                ];
            break;
            case 'Sedih':
                $psikologis = [
                    "kode" => "420038007",
                    'display' => "Feeling unhappy",
                    'ind' => 'Sedih'
                ];
            break;
            default:
            $psikologis = [
                "kode" => "74964007",
                'display' => "Other",
                'ind' => 'Lain-lain'
            ];
        }

        $formPsikologis = [
            "fullUrl" => "urn:uuid:".self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs",
                            ],
                        ],
                    ],
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8693-4",
                            "display" => "Mental Status",
                        ],
                    ],
                ],
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama,
                ],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
                "issued" => Carbon::parse($request->tgl_kunjungan)->addMinutes(10)->toIso8601String(),
                "performer" => [
                    [
                        "reference" => "Practitioner/$practitioner_uuid",
                        "display" => $nama_practitioner,
                    ],
                ],
                "valueCodeableConcept" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => $psikologis['kode'],
                            "display" => $psikologis['display'],
                        ],
                    ],
                ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
        ];
      

      $form = [
        'nadi' => $formNadi,
        'pernapasan' => $formPernapasan,
        'sistole' => $formSistole,
        'diastole' => $formDiastole,
        'suhu' => $formSuhu,
        'kesadaran' => $formKesadaran,
        'psikologis' => $formPsikologis
      ];

      return $form;
    }

    static function carePlan($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {

        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        $created = count($request->pemeriksaanfisik) ? Carbon::parse($request->pemeriksaanfisik[0]['rs3'])->toIso8601String(): Carbon::parse($request->tgl_kunjungan)->addMinutes(14)->toIso8601String();

        $diagnosaKeperawatan = $request->diagnosakeperawatan;

        $carePlan = [];

        if (count($diagnosaKeperawatan) > 0) {
            $intervensis = $diagnosaKeperawatan[0]['intervensi'];
            if (count($intervensis) > 0) {

                

                $title = "RENCANA RAWAT PASIEN ".$diagnosaKeperawatan[0]['nama'];

                // $terapeutik = $terapeutik ? $terapeutik->masterintervensi['nama'] : 'Rencana Rawat Pasien';

                for ($i=0; $i < count($intervensis) ; $i++) { 
                    $plan = [
                        "fullUrl" => "urn:uuid:".self::generateUuid(),
                        "resource" => [
                            "resourceType" => "CarePlan",
                            "status" => "active",
                            "intent" => "plan",
                            "category" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "736372004",
                                            "display" => "Discharge care plan",
                                        ],
                                    ],
                                ],
                            ],
                            "title" => $title,
                            "description" => $intervensis[$i]['masterintervensi']['nama'],
                            "subject" => [
                                "reference" => "Patient/$pasien_uuid",
                                "display" => "$request->nama",
                            ],
                            "encounter" => ["reference" => "urn:uuid:$encounter"],
                            "created" => $created,
                            "author" => [
                                "reference" => "Practitioner/".$diagnosaKeperawatan[0]['petugas']['satset_uuid'],
                                "display" => $diagnosaKeperawatan[0]['petugas']['nama'],
                            ],
                        ],
                        "request" => ["method" => "POST", "url" => "CarePlan"],
                    ];

                    $carePlan[] = $plan;
                }

                
            } else {
                $carePlan = [];
            }
        } else {
            $carePlan = [];
        }

        return $carePlan;
    }

    static function procedure($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        // $data = $request;
        $adaTindakan = [];
        // foreach ($data as $key => $value) {

            // $adaTindakan[] = $value->tindakan;
            $tindakan = $request->tindakan;
            if (count($tindakan) > 0) {
            foreach ($tindakan as $sub => $isi) {
                if ($isi->maapingprocedure !== null && $isi->maapingsnowmed !== null) {

                // setlocale(LC_ALL, 'IND');
                $dt = Carbon::parse($isi->rs3)->locale('id');
                $dt->settings(['formatFunction' => 'translatedFormat']);
                $waktuPerform = $dt->format('l, j F Y');

                $petugas_id = $isi->petugas['satset_uuid'] ?? null;
                $procedure = null;
                if ($petugas_id != null) {
                    $procedure = 
                    [
                        "fullUrl" => "urn:uuid:".self::generateUuid(),
                        "resource" => [
                            "resourceType" => "Procedure",
                            "status" => "completed",
                            "category" => [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => $isi->maapingsnowmed['kdSnowmed'] ?? '-',
                                        "display" => $isi->maapingsnowmed['display'] ?? '-',
                                    ],
                                ],
                                "text" => $isi->maapingsnowmed['display'] ?? '-'
                            ],
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                        "code" => $isi->maapingprocedure['icd9'] ?? '-',
                                        "display" => $isi->maapingprocedure['prosedur'] ?? '-',
                                    ],
                                ],
                            ],
                            "subject" => [
                                "reference" => "Patient/$pasien_uuid",
                                "display" => $request->nama,
                            ],
                            "encounter" => [
                                "reference" => "urn:uuid:$encounter",
                                "display" => $isi->keterangan." pada ".$waktuPerform
                            ],
                            "performedPeriod" => [
                                "start" => Carbon::parse($isi->rs3)->toIso8601String(),
                                "end" => Carbon::parse($isi->rs3)->addMinutes(12)->toIso8601String(),
                            ],
                            "performer" => [
                                [
                                    "actor" => [
                                        "reference" => "Practitioner/".$isi->petugas['satset_uuid'],
                                        "display" => $isi->petugas['nama'],
                                    ],
                                ],
                            ],
                            // "reasonCode" => [
                            //     [
                            //         "coding" => [
                            //             [
                            //                 "system" => "http://hl7.org/fhir/sid/icd-10",
                            //                 "code" => "A15.0",
                            //                 "display" =>
                            //                     "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                            //             ],
                            //         ],
                            //     ],
                            // ],
                            // "bodySite" => [
                            //     [
                            //         "coding" => [
                            //             [
                            //                 "system" => "http://snomed.info/sct",
                            //                 "code" => "74101002",
                            //                 "display" => "Both lungs",
                            //             ],
                            //         ],
                            //     ],
                            // ],
                            // "note" => [
                            //     ["text" => "Nebulisasi untuk melegakan sesak napas"],
                            // ],
                        ],
                        "request" => ["method" => "POST", "url" => "Procedure"],
                    ];
                }
                // $adaTindakan[] = $isi;
                $adaTindakan[] = $procedure;
                }
            }
            // }
            
        }

        return $adaTindakan;
    }

    static function planning($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $refference)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        $spri = null;
        $konsul = null;
        $kontrol = null;

        $prognosis = null;
        //   foreach ($data as $key => $value) {

        // $spri[] = $value->planning;
        $planning = $request->planning;

        
        $diagnosa = collect($request->diagnosa)->filter(function ($item) {
            return strpos($item['rs3'], 'Z') === false; 
          });
        $diag = count($diagnosa) > 0 ? $diagnosa->first() : null;

        $icd10 = $diag ? ($diag['rs3'] ?? null) : null;
        $display = $diag ? ($diag['masterdiagnosa'] ? $diag['masterdiagnosa']['rs4'] ?? null : null) : null;
        $uraian = $diag ? ($diag['masterdiagnosa'] ? $diag['masterdiagnosa']['rs3'] ?? null : null) : null;

        if (count($planning) > 0) {
            //   foreach ($planning as $sub => $isi) {
            $isi = $planning[0];
            $plann = $isi->rs4;

            $prognosisId = self::generateUuid();

            if ($plann === 'Rawat Inap' && $isi->spri !== null) {

              


                $petugas_uuid = $isi->spri['petugas'] ? $isi->spri['petugas']['satset_uuid'] : '-';
                $petugas_nama = $isi->spri['petugas'] ? $isi->spri['petugas']['nama'] : '-';

              $spri = 
              [
                "fullUrl" => "urn:uuid:".self::generateUuid(),
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" => "http://sys-ids.kemkes.go.id/servicerequest/$organization_id",
                            "value" => $organization_id,
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
                    "priority" => "routine",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "3457005",
                                    "display" => "Patient referral",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "737481003",
                                "display" => "Inpatient care management",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/$pasien_uuid"],
                    "encounter" => [
                        "reference" => "Encounter/$encounter",
                        "display" => "Kunjungan  di hari ".$tgl_kunjungan,
                    ],
                    "occurrenceDateTime" => Carbon::parse($isi->tgl)->toIso8601String(),
                    "requester" => [
                        "reference" => "Practitioner/".$petugas_uuid,
                        "display" => $petugas_nama,
                    ],
                    "performer" => [
                        ["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => $icd10,
                                    "display" => $display,
                                ],
                            ],
                        ],
                    ],
                    "locationCode" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                    "code" => "HOSP",
                                    "display" => "Hospital",
                                ],
                                // INI JIKA PAKE AMBULANCE
                                // [
                                //     "system" =>
                                //         "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                //     "code" => "AMB",
                                //     "display" => "Ambulance",
                                // ],
                            ],
                        ],
                    ],
                    "patientInstruction" => "Surat Perintah Rawat Inap RSUD MOHAMAD SALEH, Dalam Keadaan Darurat dapat Menghubungi (0335) 433119,421118",
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
              ];

             
            }


            if ($isi->listkonsul !== null) {

                $namaDokterKonsul = $isi->listkonsul['dokterkonsul'] ? $isi->listkonsul['dokterkonsul']['nama'] : '-';
                $dokterKonsulUuid = $isi->listkonsul['dokterkonsul'] ? $isi->listkonsul['dokterkonsul']['satset_uuid'] : '-';
                $tglRencanaKonsul = $isi->listkonsul['tgl_rencana_konsul'] ? $isi->listkonsul['tgl_rencana_konsul'] : '-';


                $lokasikonsul_uuid = $isi->listkonsul['lokasikonsul'] ? $isi->listkonsul['lokasikonsul']['satset_uuid'] : '-';
                $ruangankonsul = $isi->listkonsul['lokasikonsul'] ? $isi->listkonsul['lokasikonsul']['uraian'] : '-';

                $konsul = 
                [
                    "fullUrl" => "urn:uuid:".self::generateUuid(),
                    "resource" =>[
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/servicerequest/$organization_id",
                                "value" => $organization_id,
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "priority" => "routine",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "306098008",
                                        "display" => "Self-referral",
                                    ],
                                ],
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "11429006",
                                        "display" => "Consultation",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "185389009",
                                    "display" => "Follow-up visit",
                                ],
                            ],
                            "text" => "Konsultasi ke Dokter ".$namaDokterKonsul,
                        ],
                        "subject" => ["reference" => "Patient/$pasien_uuid"],
                        "encounter" => [
                            "reference" => "Encounter/$encounter",
                            "display" => "Kunjungan $request->nama di hari $tgl_kunjungan",
                        ],
                        "occurrenceDateTime" => Carbon::parse($tglRencanaKonsul)->toIso8601String(),
                        "authoredOn" => Carbon::parse($isi->tgl)->toIso8601String(),
                        "requester" => [
                            "reference" => "Practitioner/$practitioner_uuid",
                            "display" => $nama_practitioner,
                        ],
                        "performer" => [
                            ["reference" => "Practitioner/$dokterKonsulUuid", "display" => $namaDokterKonsul],
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-10",
                                        "code" => $icd10,
                                        "display" => $display,
                                    ],
                                ],
                                "text" => "Konsul ".$uraian,
                            ],
                        ],
                        "locationCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                        "code" => "OF",
                                        "display" => "Outpatient Facility",
                                    ],
                                ],
                            ],
                        ],
                        "locationReference" => [
                            [
                                "reference" => "Location/".$lokasikonsul_uuid,
                                "display" => $ruangankonsul,
                            ],
                        ],
                        "patientInstruction" => "-- ".$pasien_uuid,
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ];
            }


            if ($isi->kontrol !== null) {

                $namaDokterKonsul = $isi->kontrol['dokterkontrol'] ? $isi->kontrol['dokterkontrol']['nama'] : '-';
                $dokterKonsulUuid = $isi->kontrol['dokterkontrol'] ? $isi->kontrol['dokterkontrol']['satset_uuid'] : '-';
                $tglRencanaKonsul = $isi->kontrol['tglRencanaKontrol'] ? $isi->kontrol['tglRencanaKontrol'] : '-';


                $lokasikonsul_uuid = $isi->kontrol['lokasikontrol'] ? $isi->kontrol['lokasikontrol']['satset_uuid'] : '-';
                $ruangankonsul = $isi->kontrol['lokasikontrol'] ? $isi->kontrol['lokasikontrol']['uraian'] : '-';

                $kontrol = 
                [
                    "fullUrl" => "urn:uuid:".self::generateUuid(),
                    "resource" =>[
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/servicerequest/$organization_id",
                                "value" => $organization_id,
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "priority" => "routine",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "306098008",
                                        "display" => "Self-referral",
                                    ],
                                ],
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "11429006",
                                        "display" => "Consultation",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "185389009",
                                    "display" => "Follow-up visit",
                                ],
                            ],
                            "text" => "Kembali Kontrol ke dokter ".$namaDokterKonsul,
                        ],
                        "subject" => ["reference" => "Patient/$pasien_uuid"],
                        "encounter" => [
                            "reference" => "Encounter/$encounter",
                            "display" => "Kunjungan $request->nama di hari $tgl_kunjungan",
                        ],
                        "occurrenceDateTime" => Carbon::parse($tglRencanaKonsul)->toIso8601String(),
                        "authoredOn" => Carbon::parse($isi->tgl)->toIso8601String(),
                        "requester" => [
                            "reference" => "Practitioner/$practitioner_uuid",
                            "display" => $nama_practitioner,
                        ],
                        "performer" => [
                            ["reference" => "Practitioner/$dokterKonsulUuid", "display" => $namaDokterKonsul],
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-10",
                                        "code" => $icd10,
                                        "display" => $display,
                                    ],
                                ],
                                "text" => $uraian,
                            ],
                        ],
                        "locationCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                        "code" => "OF",
                                        "display" => "Outpatient Facility",
                                    ],
                                ],
                            ],
                        ],
                        "locationReference" => [
                            [
                                "reference" => "Location/".$lokasikonsul_uuid,
                                "display" => $ruangankonsul,
                            ],
                        ],
                        "patientInstruction" => "Masih Memerlukan Kontrol di RS",
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ];


                $ref = collect($refference)->filter(function ($item) use ($icd10) {
                    return $item['code'] === $icd10;
                })->first();

                if ($ref) {
                    $prognosis = 
                    [
                        "fullUrl" => "urn:uuid:".$prognosisId,
                        "resource" => [
                            "resourceType" => "ClinicalImpression",
                            "identifier" => [
                                [
                                    "use" => "official",
                                    "system" =>
                                        "http://sys-ids.kemkes.go.id/clinicalimpression/".$organization_id,
                                    "value" => "PROGNCOND-".$request->noreg,
                                ],
                            ],
                            "status" => "completed",
                            // "description" => $uraian,
                            "subject" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                            "encounter" => [
                                "reference" => "Encounter/$encounter",
                                "display" => "Kunjungan $request->nama di hari $tgl_kunjungan",
                            ],
                            "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
                            // "date" => "2023-10-31T03:15:31+00:00",
                            "assessor" => ["reference" => "Practitioner/".$practitioner_uuid, "display" => $nama_practitioner],
                            "problem" => [
                                ["reference" => "Condition/".$ref['reference']],
                            ],
                            // INI UNTUK refference [' Observation','QuestionnaireResponse,' DiagnosticReport','ImagingStudy']
                            // "investigation" => [
                            //     [
                            //         "code" => ["text" => "Pemeriksaan ".$uraian],
                            //         "item" => [
                            //             [
                            //                 "reference" =>
                            //                     "urn:uuid:{{DiagnosticReport_Rad}}",
                            //             ],
                            //             ["reference" => "urn:uuid:"],
                            //         ],
                            //     ],
                            // ],
                            "summary" =>
                                "Prognosis terhadap ".$uraian,
                            // "finding" => [
                            //     [
                            //         "itemCodeableConcept" => [
                            //             "coding" => [
                            //                 [
                            //                     "system" =>
                            //                         "http://hl7.org/fhir/sid/icd-10",
                            //                     "code" => $icd10,
                            //                     "display" => $display,
                            //                 ],
                            //             ],
                            //         ],
                            //         "itemReference" => [
                            //             "reference" =>
                            //                 // "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            //                 "Condition/".$ref->reference,
                            //         ],
                            //     ],
                            //     [
                            //         "itemCodeableConcept" => [
                            //             "coding" => [
                            //                 [
                            //                     "system" =>
                            //                         "http://hl7.org/fhir/sid/icd-10",
                            //                     "code" => "E44.1",
                            //                     "display" =>
                            //                         "Mild protein-calorie malnutrition",
                            //                 ],
                            //             ],
                            //         ],
                            //         "itemReference" => [
                            //             "reference" =>
                            //                 "urn:uuid:{{Condition_DiagnosisSekunder}}",
                            //         ],
                            //     ],
                            // ],

                            # Kumpulan Code Prognosis di http://terminology.kemkes.go.id/CodeSystem/clinical-term
                            # 1. Prognosis baik || PR000001
                            # 2. Prognosis dubia et bonam / cenderung baik || PR000002
                            # 3. Prognosis dubia et malam / cenderung tidak baik || PR000003
                            # 4. Prognosis tidak baik || PR000004


                            # Kumpulan Code Prognosis di http://snomed.info/sct
                            # 1. Prognosis good || 170968001
                            # 2. Fair prognosis || 65872000
                            # 3. Guarded prognosis || 67334001
                            # 4. Prognosis bad || 170969009

                            "prognosisCodeableConcept" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "65872000",
                                            "display" => "Fair prognosis",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "request" => ["method" => "POST", "url" => "ClinicalImpression"],
                    ];
                }
            }
            
        } else {
            // Untuk Prognosis ... Cek dulu apakah Pasien dari Konsul Internal Atau Bukan
            $ref = collect($refference)->filter(function ($item) {
                return $item['jenis'] === 'Primer';
            })->first();

            $pasienKonsul = !isEmpty($request->rs4);
            
            if ($ref && !$pasienKonsul) {
                $prognosis = 
                [
                    "fullUrl" => "urn:uuid:".self::generateUuid(),
                    "resource" => [
                        "resourceType" => "ClinicalImpression",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/clinicalimpression/".$organization_id,
                                "value" => "PROGNCOND-".$request->noreg,
                            ],
                        ],
                        "status" => "completed",
                        // "description" => $ref['displayInd'],
                        "subject" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                        "encounter" => [
                            "reference" => "Encounter/$encounter",
                            "display" => "Kunjungan $request->nama di hari $tgl_kunjungan",
                        ],
                        "effectiveDateTime" => Carbon::parse($request->tgl_kunjungan)->toIso8601String(),
                        // "date" => "2023-10-31T03:15:31+00:00",
                        "assessor" => ["reference" => "Practitioner/".$practitioner_uuid, "display" => $nama_practitioner],
                        "problem" => [
                            ["reference" => "Condition/".$ref['reference']],
                        ],
                        // INI UNTUK refference [' Observation','QuestionnaireResponse,' DiagnosticReport','ImagingStudy']
                        // "investigation" => [
                        //     [
                        //         "code" => ["text" => "Pemeriksaan ".$uraian],
                        //         "item" => [
                        //             [
                        //                 "reference" =>
                        //                     "urn:uuid:{{DiagnosticReport_Rad}}",
                        //             ],
                        //             ["reference" => "urn:uuid:"],
                        //         ],
                        //     ],
                        // ],
                        "summary" => "PROGNOSIS TERHADAP ". $ref['displayInd'],
                        // "finding" => [
                        //     [
                        //         "itemCodeableConcept" => [
                        //             "coding" => [
                        //                 [
                        //                     "system" =>
                        //                         "http://hl7.org/fhir/sid/icd-10",
                        //                     "code" => $icd10,
                        //                     "display" => $display,
                        //                 ],
                        //             ],
                        //         ],
                        //         "itemReference" => [
                        //             "reference" =>
                        //                 // "urn:uuid:{{Condition_DiagnosisPrimer}}",
                        //                 "Condition/".$ref->reference,
                        //         ],
                        //     ],
                        //     [
                        //         "itemCodeableConcept" => [
                        //             "coding" => [
                        //                 [
                        //                     "system" =>
                        //                         "http://hl7.org/fhir/sid/icd-10",
                        //                     "code" => "E44.1",
                        //                     "display" =>
                        //                         "Mild protein-calorie malnutrition",
                        //                 ],
                        //             ],
                        //         ],
                        //         "itemReference" => [
                        //             "reference" =>
                        //                 "urn:uuid:{{Condition_DiagnosisSekunder}}",
                        //         ],
                        //     ],
                        // ],

                        # Kumpulan Code Prognosis di http://terminology.kemkes.go.id/CodeSystem/clinical-term
                        # 1. Prognosis baik || PR000001
                        # 2. Prognosis dubia et bonam / cenderung baik || PR000002
                        # 3. Prognosis dubia et malam / cenderung tidak baik || PR000003
                        # 4. Prognosis tidak baik || PR000004


                        # Kumpulan Code Prognosis di http://snomed.info/sct
                        # 1. Prognosis good || 170968001
                        # 2. Fair prognosis || 65872000
                        # 3. Guarded prognosis || 67334001
                        # 4. Prognosis bad || 170969009

                        "prognosisCodeableConcept" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "170968001",
                                        "display" => "Prognosis good",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "ClinicalImpression"],
                ];
            }
        }
        
        //   }
    
        $data = [
            'spri'=>$spri,
            'konsul'=>$konsul,
            'kontrol'=>$kontrol,
            'prognosis'=>$prognosis,
            'refference' => $refference,
            'pasienKonsul'=> !isEmpty($request->rs4)

        ];
        return $data;
        
    }

    static function allergyIntoleran($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id)
    {

        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        $created = count($request->pemeriksaanfisik) ? Carbon::parse($request->pemeriksaanfisik[0]['rs3'])->toIso8601String(): Carbon::parse($request->tgl_kunjungan)->addMinutes(14)->toIso8601String();

        $allergy = null;

        $anamnesis = $request->anamnesis;
        if (count($anamnesis) > 0) {

            if ($anamnesis[0]['riwayatalergi'] === null || $anamnesis[0]['riwayatalergi'] === '' || $anamnesis[0]['riwayatalergi'] === 'Tidak ada Alergi' || $anamnesis[0]['riwayatalergi'] === 'Tidak Ada Alergi,') {
                $allergy = null;
            } else {

                $anamnesisAllergi = preg_replace("/[^a-zA-Z0-9]/", ",", $anamnesis[0]['riwayatalergi']);
                $cek = Allergy::where('nama','like','%'.$anamnesisAllergi.'%')->first();

                if ($cek) {
                    $allergy = 
                    [
                        "fullUrl" => "urn:uuid:".self::generateUuid(),
                        "resource" => [
                            "resourceType" => "AllergyIntolerance",
                            "identifier" => [
                                [
                                    "system" =>
                                        "http://sys-ids.kemkes.go.id/allergy/".$organization_id,
                                    "use" => "official",
                                    "value" => $cek->kode,
                                ],
                            ],
                            "clinicalStatus" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                        "code" => "active",
                                        "display" => "Active",
                                    ],
                                ],
                            ],
                            "verificationStatus" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                        "code" => "confirmed",
                                        "display" => "Confirmed",
                                    ],
                                ],
                            ],
                            "category" => [$cek->codename],
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => $cek->kdSnowmed,
                                        "display" => $cek->display,
                                    ],
                                ],
                                "text" => $anamnesis[0]['keteranganalergi'] ?? '-',
                            ],
                            "patient" => [
                                "reference" => "Patient/".$pasien_uuid,
                                "display" => $request->nama,
                            ],
                            "encounter" => [
                                "reference" => "urn:uuid:".$encounter,
                                "display" => "Kunjungan $request->nama di hari ".$tgl_kunjungan,
                            ],
                            "recordedDate" => Carbon::parse($anamnesis[0]['created_at'])->toIso8601String(),
                            "recorder" => ["reference" => "Practitioner/".$practitioner_uuid],
                        ],
                        "request" => ["method" => "POST", "url" => "AllergyIntolerance"],
                    ];
                } else {
                    $allergy = null;
                }
                
            }
            
            
        }else {
            $allergy = null;
        }

        

        return $allergy;
    }
    static function screeningGizi($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        
        
    }
    static function laborats($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $specimenSnomeds)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        $laborats = $request->laborats;
        $data = [];
        $drs=[
            ['id' => '10001635853', 'text' => 'dr.BOBY MULYADI, Sp.PK'],
            ['id' => '10012301444', 'text' => 'dr. ROSID ACHMAD, Sp. PK'],
        ];
        if (count($laborats) > 0) {
            for ($i=0; $i < count($laborats) ; $i++) { 
                $nota = $laborats[$i]['nota'];
                $detailsPermintaans = collect($laborats[$i]['details'])->map(function ($item) {
                    $obj = (object) $item;
                    $obj->GROUP = $obj['pemeriksaanlab']['rs21'] === '' ? $obj['pemeriksaanlab']['rs2'] : $obj['pemeriksaanlab']['rs21'];
                    $obj->IS_PAKET = $obj['pemeriksaanlab']['rs21'] === '' ? false : true;
                    $obj->LOINC = $obj['pemeriksaanlab']['loinc']; // ini jika paket gak dipake
                    $obj->DISPLAY_LOINC = $obj['pemeriksaanlab']['display_loinc'];
                    $obj->ADA_SPESIMEN = $obj['pemeriksaanlab']['jenis_spesimen'];

                    $metode = !empty($obj['metode']) ? strtolower($obj['metode']) : null;
                    $satuan = $obj['pemeriksaanlab']['satuan'];
                    $obj->SATUAN = $satuan;
                    $arr = $obj['pemeriksaanlab']['loinclab'];
                    $ARRX = null;
                    if (count($arr) > 0 && count($arr) === 1) {
                        $ARRX = $arr[0];
                    } elseif (count($arr) > 0 && count($arr) > 1) {
                        $filt = collect($arr)->filter(function($item) use ($metode) { 
                            return Str::contains(strtolower($item['metode_analisis']), $metode);
                        });
                        $reIndexed = array_values($filt->toArray());
                        if (count($reIndexed) === 0) {
                            $ARRX = $arr[0];
                        } else {
                            $ARRX = $reIndexed[0];
                        }

                    }

                    $obj->PEMETAAN = $ARRX;

                    $obj->puasa_pasien = $obj['puasa_pasien'];
                    return $obj;
                })->groupBy('GROUP');

                # kirim data laborat yg hanya ada details loinc nya

                foreach ($detailsPermintaans as $key => $value) {
                    $servisRequestId = self::generateUuid(); // berdasarkan nota

                    $puasa = $laborats[$i]['puasa_pasien'];
                    $tgl_permintaan = $laborats[$i]['tgl_permintaan'];
                    $cito = $laborats[$i]['prioritas_pemeriksaan'];
                    $diagnosa_masalah = $laborats[$i]['diagnosa_masalah'];
                    $catatan = $laborats[$i]['catatan_permintaan'];
                    $kode = $value[0]['pemeriksaanlab']['rs1'];
                    $hasil = $value[0]['rs21'] === '' ? null : (float) $value[0]['rs21'];
                    $satuan = $value[0]['pemeriksaanlab']['rs22'] === '' ? null : $value[0]['pemeriksaanlab']['rs22'];
                    $HL = $value[0]['rs27'] ?? null;
                    $tgl_selesai = $value[0]['rs29'] ?? Carbon::now();

                    $LOINC = !$value[0]['IS_PAKET'] ? ($value[0]['LOINC'] === '' || $value[0]['LOINC'] === null ? null : $value[0]['LOINC']) : null;
                    $DISPLAY_LOINC = !$value[0]['IS_PAKET'] ? ($value[0]['DISPLAY_LOINC'] ?? null) : null;

                    $serviceRequests = null;
                    $hasil_lab = null;
                    $paket = $value[0]['IS_PAKET'];

                    $spesimen_uuid = self::generateUuid();
                    $idPemeriksaan = $value[0]['id'];



                    $pemetaan = $value[0]['PEMETAAN'];
                    $snomedSpesimen = null;
                    $spesimenx=null;
                    $diagnosticReport = null;
                    
                    // $brwse = null;
                    // if ($LOINC !== null) {
                    //     $brwse = count(BridgingLoincHelper::getLoincByKode($LOINC)['Results'])> 0 ? BridgingLoincHelper::getLoincByKode($LOINC)['Results'][0]: null;
                    //     $spesimen = $brwse['SYSTEM'] ?? null;
                    // }

                    if (!$paket && $LOINC !== null && $hasil !== null) {

                        

                        if($pemetaan != null){

                            // service request
                            $serviceRequests = 
                            [
                                "fullUrl" => "urn:uuid:".$servisRequestId,
                                "resource" => [
                                    "resourceType" => "ServiceRequest",
                                    "identifier" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/servicerequest/".$organization_id,
                                                "value" => "RJ-{$idPemeriksaan}", // ini nota
                                        ]
                                        // [
                                        //     "use" => "usual",
                                        //     "type" => [
                                        //         "coding" => [
                                        //             [
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v2-0203",
                                        //                 "code" => "ACSN",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     "system" =>
                                        //         "http://sys-ids.kemkes.go.id/acsn/{{Org_ID}}",
                                        //     "value" => "",
                                        // ],
                                    ],
                                    "status" => "active",
                                    "intent" => "order",
                                    "category" => [
                                        [
                                            "coding" => [
                                                [
                                                    "system" => "http://snomed.info/sct",
                                                    "code" => "108252007",
                                                    "display" => "Laboratory procedure",
                                                ],
                                            ],
                                        ],
                                    ],
                                    "priority" => $cito === 'Tidak' ? "routine" : "urgent", // "routine" "urgent"  yg cito harap pake urgent
                                    "code" => [
                                        "coding" => [
                                            [
                                                "system" => "http://loinc.org",
                                                // "code" => "24648-8",
                                                "code" => $LOINC,
                                                "display" => $DISPLAY_LOINC,
                                            ],
                                        ],
                                        "text" => "Pemeriksaan ".$value[0]['GROUP'],
                                    ],
                                    // "orderDetail" => [
                                    //     [
                                    //         "coding" => [
                                    //             [
                                    //                 "system" =>
                                    //                     "http://dicom.nema.org/resources/ontology/DCM",
                                    //                 "code" => "DX",
                                    //             ],
                                    //         ],
                                    //         "text" => "Modality Code: DX",
                                    //     ],
                                    //     [
                                    //         "coding" => [
                                    //             [
                                    //                 "system" =>
                                    //                     "http://sys-ids.kemkes.go.id/ae-title",
                                    //                 "display" => "XR0001",
                                    //             ],
                                    //         ],
                                    //     ],
                                    // ],
                                    "subject" => ["reference" => "Patient/".$pasien_uuid],
                                    "encounter" => ["reference" => "Encounter/".$encounter],
                                    "occurrenceDateTime" => Carbon::parse($tgl_permintaan)->toIso8601String(),
                                    "requester" => [
                                        "reference" => "Practitioner/".$practitioner_uuid,
                                        "display" => $nama_practitioner,
                                    ],
                                    "performer" => [
                                        [
                                            "reference" => "Practitioner/".$drs[1]['id'],
                                            "display" => $drs[1]['text'],
                                        ],
                                    ],
                                    "reasonCode" => [
                                        [
                                            "text" =>
                                                "Permintaan pemeriksaan untuk diagnosa masalah ".$diagnosa_masalah,
                                        ],
                                    ]
                                    // "reasonReference"=> [
                                    //     ["reference"=> "Condition/{{Condition_KeluhanUtama}}"]                                    
                                    // ],
                                    // "note"=> [
                                    //     ["text"=> "Pasien tidak perlu berpuasa terlebih dahulu"]
                                    // ],
                                    // "supportingInfo" => [
                                    //     // ["reference" => "urn:uuid:{{Observation_PraRad}}"],
                                    //     // ["reference" => "urn:uuid:{{Procedure_PraRad}}"],
                                    //     ["reference" => "urn:uuid:Procedure/{{Procedure_StatusPuasa_Paket}}"],
                                    // ],
                                ],
                                "request" => ["method" => "POST", "url" => "ServiceRequest"],
                            ];

                            // SPESIMEN
                            $snomedSpesimen = self::cariSpecimenSnomed($specimenSnomeds, $pemetaan['spesimen']);
                            $spesimenx =
                            [
                                "fullUrl" => "urn:uuid:".$spesimen_uuid,
                                "resource" => [
                                    "resourceType" => "Specimen",
                                    "identifier" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/specimen/".$organization_id,
                                            "value" => "SPE-{$idPemeriksaan}",
                                            "assigner" => ["reference" => "Organization/".$organization_id],
                                        ],
                                    ],
                                    "status" => "available", // * ["available" "unavailable" "unsatisfactory" "entered-in-error" ]
                                    "type" => [ //*
                                        "coding" => [
                                            [
                                                "system" => $snomedSpesimen['system'],
                                                "code" => "{$snomedSpesimen['code']}",
                                                "display" => $snomedSpesimen['display'],
                                            ],
                                        ],
                                    ],
                                    "condition" => [["text" => "Kondisi Spesimen Baik"]],
                                    // "collection" => [
                                    //     "method" => [
                                    //         "coding" => [
                                    //             [
                                    //                 "system" => "http://snomed.info/sct",
                                    //                 "code" => "82078001",
                                    //                 "display" =>
                                    //                     "Collection of blood specimen for laboratory",
                                    //             ],
                                    //         ],
                                    //     ],
                                    //     "collectedDateTime" => "2023-03-27T15:00:00+00:00",
                                    //     "quantity" => ["value" => 30, "unit" => "mL"],
                                    //     "collector" => [
                                    //         "reference" => "Practitioner/N10000001",
                                    //         "display" => "Dokter Bronsig",
                                    //     ],
                                    //     ['F','NF','NG] ['Patient was fasting prior to the procedure.',
                                    //     'The patient indicated they did not fast prior to the procedure.',
                                    //     'Not Given - Patient was not asked at the time of the procedure.' ]
                                    //     "fastingStatusCodeableConcept" => [ 
                                    //         "coding" => [
                                    //             [
                                    //                 "system" =>
                                    //                     "http://terminology.hl7.org/CodeSystem/v2-0916",
                                    //                 "code" => "NF",
                                    //                 "display" =>
                                    //                     "The patient indicated they did not fast prior to the procedure.",
                                    //             ],
                                    //         ],
                                    //     ],
                                    // ],
                                    "subject" => [ // *
                                        "reference" => "Patient/".$pasien_uuid,
                                        "display" => $request->nama,
                                    ],
                                    "request" => [
                                        [
                                            "reference" =>
                                                "ServiceRequest/".$servisRequestId,
                                        ],
                                    ],
                                    // "extension" => [
                                    //     [
                                    //         "url" =>
                                    //             "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedTime",
                                    //         "valueDateTime" => "2023-03-27T15:15:00+00:00",
                                    //     ],
                                    //     [
                                    //         "url" =>
                                    //             "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedPerson",
                                    //         "valueContactDetail" => [
                                    //             "name" => "Burhan",
                                    //             "telecom" => [
                                    //                 ["system" => "phone", "value" => "021-5375162"],
                                    //             ],
                                    //         ],
                                    //     ],
                                    //     [
                                    //         "url" =>
                                    //             "https://fhir.kemkes.go.id/r4/StructureDefinition/ReceivedPerson",
                                    //         "valueReference" => [
                                    //             "reference" => "Practitioner/10006926841",
                                    //             "display" => "Dr. John Doe",
                                    //         ],
                                    //     ],
                                    // ],
                                    "receivedTime" => Carbon::parse($tgl_permintaan)->toIso8601String(),
                                    // "processing" => [
                                    //     ["timeDateTime" => "2023-03-28T16:30:00+00:00"],
                                    // ],
                                ],
                                "request" => ["method" => "POST", "url" => "Specimen"],
                            ];

                            // HASIL
                            $hasilId = self::generateUuid();
                            $hasil_lab = 
                            [
                                "fullUrl" => "urn:uuid:".$hasilId,
                                "resource" => 
                                [
                                    "resourceType" => "Observation",
                                    "identifier" => [
                                        [
                                            "system" => "http://sys-ids.kemkes.go.id/observation/".$organization_id,
                                            "value" => "LAB-{$idPemeriksaan}",
                                        ],
                                    ],
                                    "status" => "final",
                                    "category" => [
                                        [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                                    "code" => "laboratory",
                                                    "display" => "Laboratory",
                                                ],
                                            ],
                                        ],
                                    ],
                                    "code" => [
                                        "coding" => [
                                            [
                                                "system" => "http://loinc.org",
                                                "code" => $LOINC,
                                                "display" => $DISPLAY_LOINC,
                                            ],
                                        ],
                                    ],
                                    "subject" => ["reference" => "Patient/".$pasien_uuid],
                                    "encounter" => ["reference" => "Encounter/".$encounter],
                                    "effectiveDateTime" => Carbon::parse($tgl_selesai)->toIso8601String(),
                                    "issued" => Carbon::parse($tgl_selesai)->toIso8601String(),
                                    "performer" => [
                                        ["reference" => "Practitioner/".$drs[1]['id']],
                                        ["reference" => "Organization/".$organization_id],
                                    ],
                                    // "specimen" => ["reference" => "Specimen/"],
                                    "basedOn" => [["reference" => "ServiceRequest/".$servisRequestId]],
                                    
                                    
                                    
                                    // "referenceRange" => [
                                    //     [
                                    //         "high" => [
                                    //             "value" => 200,
                                    //             "unit" => "mg/dL",
                                    //             "system" => "http://unitsofmeasure.org",
                                    //             "code" => "mg/dL",
                                    //         ],
                                    //         "text" => "Normal",
                                    //     ],
                                    //     [
                                    //         "low" => [
                                    //             "value" => 201,
                                    //             "unit" => "mg/dL",
                                    //             "system" => "http://unitsofmeasure.org",
                                    //             "code" => "mg/dL",
                                    //         ],
                                    //         "high" => [
                                    //             "value" => 239,
                                    //             "unit" => "mg/dL",
                                    //             "system" => "http://unitsofmeasure.org",
                                    //             "code" => "mg/dL",
                                    //         ],
                                    //         "text" => "Borderline high",
                                    //     ],
                                    //     [
                                    //         "low" => [
                                    //             "value" => 240,
                                    //             "unit" => "mg/dL",
                                    //             "system" => "http://unitsofmeasure.org",
                                    //             "code" => "mg/dL",
                                    //         ],
                                    //         "text" => "High",
                                    //     ],
                                    // ],
                                ],
                                "request" => ["method" => "POST", "url" => "Observation"],
                            ];
                            $includHasil = 
                            [
                                "valueQuantity" => [
                                        "value" => $hasil,
                                        "unit" => $pemetaan['satuan'], // ini satuan
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => $pemetaan['satuan'],
                                ]
                            ];

                            
                            $critical = self::criticalx($HL);
                            $includeHL =
                            [
                                "interpretation" => 
                                [
                                    [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                                "code" => $critical['code'],
                                                "display" => $critical['display'],
                                            ],
                                        ],
                                    ],
                                ],
                            ];
                            

                            if ($hasil) {
                               $hasil_lab['resource']['valueQuantity'] = $includHasil['valueQuantity'];
                            }

                            if ($HL) {
                                $hasil_lab['resource']['interpretation'] = $includeHL['interpretation'];
                            }


                            // DIAGNOSTIK REPORT
                            $diagnosticReport =
                            [
                                "fullUrl" => "urn:uuid:".self::generateUuid(),
                                "resource" => [
                                    "resourceType" => "DiagnosticReport",
                                    "identifier" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/diagnostic/$organization_id/lab",
                                            "use" => "official",
                                            "value" => "DR-{$idPemeriksaan}",
                                        ],
                                    ],
                                    "status" => "amended",
                                    "category" => [
                                        [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/v2-0074",
                                                    "code" => "LAB",
                                                    "display" => "Laboratory",
                                                ],
                                            ],
                                        ],
                                    ],
                                    "code" => [
                                        "coding" => [
                                            [
                                                "system" => "http://loinc.org",
                                                "code" => $LOINC,
                                                "display" => $DISPLAY_LOINC,
                                            ],
                                        ],
                                    ],
                                    "subject" => ["reference" => "Patient/".$pasien_uuid],
                                    "encounter" => [
                                        "reference" =>
                                            "Encounter/".$encounter,
                                    ],
                                    "effectiveDateTime" => Carbon::parse($tgl_selesai)->toIso8601String(),
                                    "issued" => Carbon::parse($tgl_selesai)->toIso8601String(),
                                    "performer" => [
                                        ["reference" => "Practitioner/".$drs[0]['id']],
                                        ["reference" => "Organization/".$organization_id],
                                    ],
                                    // "imagingStudy" => [
                                    //     [
                                    //         "reference" =>
                                    //             "urn:uuid:c4f3bfe3-91cd-40c4-b986-000c2150f051",
                                    //     ],
                                    // ],
                                    
                                    "specimen" => [
                                        [
                                            "reference" => "Specimen/".$spesimen_uuid,
                                        ],
                                    ],
                                    "result" => [
                                        [
                                            "reference" => "Observation/".$hasilId,
                                        ],
                                    ],
                                    "basedOn" => [
                                        [
                                            "reference" => "ServiceRequest/".$servisRequestId,
                                        ],
                                    ],
                                    "conclusion" => "",
                                ],
                                "request" => ["method" => "POST", "url" => "DiagnosticReport"],
                            ];
                        }
                        
                            
                        
                    }
                    
                    
                    $data[] = [
                        'serviceRequests' => $serviceRequests,
                        'hasil' => $hasil_lab,
                        'spesimen' => $spesimenx,
                        'diagnosticReport' => $diagnosticReport,
                        'pemetaan' => $pemetaan,
                        'snomedSpesimen' => $snomedSpesimen,
                        'value' => $hasil,
                        'loinc' => $LOINC,
                        'paket' => $paket,
                        'paket_loinc' => $value[0]['pemeriksaanlab']['loinc_paket'] ?? null,
                        'HL'=> $HL,
                        'id_pemeriksaan'=> $idPemeriksaan,
                        'nama_pemeriksaan'=> $value[0]['GROUP'],
                        'datax' => $value[0]
                        // 'datax' => $value[0]
                    ];
                }

            }

            
        }



        return $data;
        
        
    }

    static  function criticalx($val)
    {
        $highLow = ['!','1'];
        $code = null;
        $display = null;
        switch ($val) {
            case 'H':
              $code= 'H';
              $display = 'High';
              break;
            case 'L':
              $code = 'L';
              $display = 'Low';
              break;
            case 'H*':
              $code = 'HH';
              $display = 'Critical high';
              break;
            case 'L*':
              $code = 'LL';
              $display = 'Critical low';
              break;
            case 'N':
              $code = 'N';
              $display = 'Normal';
              break;
            default:
              //code block
          }

        return [
            'code' => $code,
            'display' => $display
        ];
    }
    static  function cariSpecimenSnomed($arr, $val)
    {
        // INI JIKA PAKE LIKE
        // $finder = collect($arr)->filter(function ($item) use ($val) {
        //     return Str::contains(strtolower($item['spesimen']), strtolower(($val)));
        // });

        // $reIndexed = array_values($finder->toArray());
        // if (count($reIndexed) === 0) {
        //     return  $finder[0];
        // } 
        // return $reIndexed[0];

        return collect($arr)->filter(function($item) use ($val) {
            return trim(strtolower($item['spesimen'])) === trim(strtolower($val));
        })->first();
    }
    


    static function apotek($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';

        $resep = $request->apotek;

        // Pengiriman data peresepan obat akan menggunakan 2 resources yaitu Medication dan MedicationRequest. 
        // Resource Medication akan mencatatkan data umum terkait obat yang akan diresepkan. 
        // Sedangkan resource MedicationRequest akan digunakan untuk mengirimkan data terkait peresepan obat seperti jumlah yang diresepkan, 
        // instruksi minum obat dan lain-lain. 
        // Kedua data ini dikirimkan secara bersamaan sebagai 1 paket yaitu Medication dan MedicationRequest. 
        // Satu payload Medication dan MedicationRequest hanya dapat digunakan untuk peresepan 1 jenis obat saja. 
        // Apabila terdapat 2 jenis obat yang diresepkan, maka dikirimkan 2 paket Medication dan MedicationRequest.


        /**
         * Resource Medication
         * Keterangan : +
            * NC : Non-compound (non racikan)
            * SD : Give of such doses (dtd)
            * EP : Divide into equal part (tablet dipecah).
        */

        $diagnosa = collect($request->diagnosa)->filter(function ($item) {
            return strpos($item['rs3'], 'Z') === false; 
          });
        $diag = count($diagnosa) > 0 ? $diagnosa->first() : null;

        $icd10 = $diag ? ($diag['rs3'] ?? '-') : '-';
        $display = $diag ? ($diag['masterdiagnosa'] ? $diag['masterdiagnosa']['rs4'] ?? '-' : '-') : '-';
        $uraian = $diag ? ($diag['masterdiagnosa'] ? $diag['masterdiagnosa']['rs3'] ?? '-' : '-') : '-';

        $locationFarmasiRajal = '692c5cbc-3355-4044-ab0a-657392a20a10';
        $displayFarmasiRajal = 'Farmasi Poli Rawat Jalan';

        $kirimObatNonRacikan = [];
        $kirimObatRacikan = [];
        $preview = [];


        $medicationRequestNonRacikan = [];

        if (count($resep) > 0) {
            for ($i=0; $i < count($resep) ; $i++) { 
                $nonRacikan = $resep[$i]['rincian'];

                $noresep = $resep[$i]['noresep']?? '-';
                $tgl_kirim = $resep[$i]['tgl_kirim']?? Carbon::now();
                $tgl_selesai = $resep[$i]['tgl_selesai']?? Carbon::now();


                $apoteker_uuid = $resep[$i]['petugas'] ? $resep[$i]['petugas']['satset_uuid'] : null;
                $nama_apoteker = $resep[$i]['petugas'] ? $resep[$i]['petugas']['nama'] : '-';


                $idResep = $resep[$i]['id'];
                if (count($nonRacikan) > 0) {
                    # kirim obat non racikan yang hanya ada kode kfa nya
                    for ($j=0; $j < count($nonRacikan) ; $j++) {

                        $kode_kfa_93 = $nonRacikan[$j]['mobat']['kode_kfa_93'];
                        $kode_kfa = $nonRacikan[$j]['mobat']['kode_kfa'];
                        $kfa = $nonRacikan[$j]['mobat']['kfa'];
                        $medication_id = self::generateUuid();
                        if ($kode_kfa_93 != null && $kode_kfa != null && $kfa !== null) {


                            $display = $nonRacikan[$j]['mobat']['kfa']['response']['result']['name'] ?? '-';
                            $gudang = $nonRacikan[$j]['mobat']['gudang'] ?? '-';
                            $kdobat = $nonRacikan[$j]['kdobat'];
                            $idObat = $nonRacikan[$j]['mobat']['id'];
                            $idRincian = $nonRacikan[$j]['id'];
                            $dosage_form = $nonRacikan[$j]['mobat']['kfa']['dosage_form']['code'] ?? '-';
                            $dosage_form_display = $nonRacikan[$j]['mobat']['kfa']['dosage_form']['name'] ?? '-';
                            $routeCode = $nonRacikan[$j]['mobat']['kfa']['response']['result']['rute_pemberian']['code'] ?? '-';
                            $routeName = $nonRacikan[$j]['mobat']['kfa']['response']['result']['rute_pemberian']['name'] ?? '-';

                            


                            $konsumsiX = (int)$nonRacikan[$j]['konsumsi'] >=30 ?? false;
                            $kronis = (int)$nonRacikan[$j]['mobat']['status_kronis'] === '1' ?? false;

                            $longTerm = $konsumsiX && $kronis;

                            $bagi = ($nonRacikan[$j]['konsumsi_perhari'] === 0 || $nonRacikan[$j]['konsumsi_perhari'] === null || $nonRacikan[$j]['konsumsi_perhari'] === '') ? 0 : $nonRacikan[$j]['qty'] / $nonRacikan[$j]['konsumsi_perhari'];
                            // return $bagi;
                            $pembagian = $bagi === 0 ? 0 : ceil($bagi);

                            $tglObatHabis = Carbon::parse($tgl_selesai)->addDays($pembagian);
                            $medicationRequest_id = self::generateUuid();



                            $tambahan = 
                            [
                                "reasonCode" => [
                                        [
                                            "coding" => [
                                                [
                                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                                    "code" => $icd10,
                                                    "display" => $display,
                                                ],
                                            ],
                                        ],
                                    ],
                                    "courseOfTherapyType" => [
                                        "coding" => [
                                            [
                                                "system" =>"http://terminology.hl7.org/CodeSystem/medicationrequest-course-of-therapy",
                                                "code" => "continuous",
                                                "display" => "Continuing long term therapy",
                                            ],
                                        ],
                                    ],
                            ];

                            // $a = "A".$idRincian;
                            // $bbb = "B".$idRincian;
                            // $c = "C".$idRincian;
                            // $d = "D".$idRincian;
                            // $eee = "E".$idRincian;
                            // $f = "F".$idRincian;
                            $a = Str::random(20);
                            $bbb = Str::random(20);
                            $c = Str::random(20);
                            $d = Str::random(20);
                            $eee = Str::random(20);
                            $f = Str::random(20);
                             // Medication
                            if ($pembagian > 0) {


                                // Medication For Request
                                
                                $medicationForRequest =   
                                [
                                    "fullUrl" => "urn:uuid:".$medication_id,
                                    "resource" => [
                                        "resourceType" => "Medication",
                                        "meta" => [
                                            "profile" => [
                                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                                            ],
                                        ],
                                        "identifier" => [
                                            [
                                                "system" =>
                                                    "http://sys-ids.kemkes.go.id/medication/".$organization_id,
                                                "use" => "official",
                                                "value" => $a
                                            ],
                                        ],
                                        "code" => [
                                            "coding" => [
                                                [
                                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                                    "code" => $kode_kfa,
                                                    "display" => $display
                                                ],
                                            ],
                                        ],
                                        "status" => "active",
                                        // "manufacturer" => ["reference" => "Organization/".$organization_id],
                                        "form" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                                    "code" => $dosage_form,
                                                    "display" => $dosage_form_display,
                                                ],
                                            ],
                                        ],

                                        // khusus racikan
                                        // "ingredient" => [
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000330",
                                        //                     "display" => "Rifampin",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 150,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000328",
                                        //                     "display" => "Isoniazid",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 75,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000329",
                                        //                     "display" => "Pyrazinamide",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 400,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000288",
                                        //                     "display" => "Ethambutol",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 275,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        // ],
                                        "extension" => [
                                            [
                                                "url" =>
                                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                                "valueCodeableConcept" => [
                                                    "coding" => [
                                                        [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                                            "code" => "NC",
                                                            "display" => "Non-compound",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    "request" => ["method" => "POST", "url" => "Medication"],
                                ];


                                // MedicationRequest
                                
                                
                                $medicationRequest =
                                [
                                    "fullUrl" => "urn:uuid:".$medicationRequest_id,
                                    "resource" => [
                                        "resourceType" => "MedicationRequest",
                                        "identifier" => [
                                            [
                                                "system" =>
                                                    "http://sys-ids.kemkes.go.id/prescription/".$organization_id,
                                                "use" => "official",
                                                "value" => $bbb
                                            ],
                                            [
                                                "system" =>
                                                    "http://sys-ids.kemkes.go.id/prescription-item/".$organization_id,
                                                "use" => "official",
                                                "value" => $c
                                            ],
                                        ],
                                        "status" => "completed",
                                        "intent" => "order",
                                        "category" => [
                                            [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                                        "code" => "outpatient",
                                                        "display" => "Outpatient",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        "priority" => "routine",
                                        "medicationReference" => [
                                            "reference" => "Medication/".$medication_id,
                                            "display" => $display,
                                        ],
                                        "subject" => [
                                            "reference" => "Patient/".$pasien_uuid,
                                            "display" => $request->nama,
                                        ],
                                        "encounter" => [
                                            "reference" => "Encounter/".$encounter,
                                        ],
                                        "authoredOn" => Carbon::parse($tgl_kirim)->toIso8601String(),
                                        "requester" => [
                                            "reference" => "Practitioner/".$practitioner_uuid,
                                            "display" => $nama_practitioner,
                                        ],
                                        
                                        "dosageInstruction" => [
                                            [
                                                "sequence" => 1,
                                                "text" => $nonRacikan[$j]['aturan']." ".$nonRacikan[$j]['keterangan'],
                                                "additionalInstruction" => [
                                                    ["text" => $nonRacikan[$j]['keterangan']]
                                                ],
                                                "patientInstruction" => $nonRacikan[$j]['aturan']." ".$nonRacikan[$j]['keterangan'],
                                                "timing" => [
                                                    "repeat" => [
                                                        "frequency" => $nonRacikan[$j]['konsumsi_perhari'],
                                                        "period" => 1,
                                                        "periodUnit" => "d",
                                                    ],
                                                ],
                                                "route" => [
                                                    "coding" => [
                                                        [
                                                            "system" => "http://www.whocc.no/atc",
                                                            "code" => $routeCode,
                                                            "display" => $routeName,
                                                        ],
                                                    ],
                                                ],
                                                // "doseAndRate" => [
                                                //     [
                                                //         "type" => [
                                                //             "coding" => [
                                                //                 [
                                                //                     "system" =>
                                                //                         "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                //                     "code" => "ordered",
                                                //                     "display" => "Ordered",
                                                //                 ],
                                                //             ],
                                                //         ],
                                                //         "doseQuantity" => [
                                                //             "value" => $nonRacikan[$j]['qty'],
                                                //             "unit" => $nonRacikan[$j]['mobat']['satuan_k'],
                                                //             "system" =>
                                                //                 "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                                //             "code" => "TAB",
                                                //         ],
                                                //     ],
                                                // ],
                                            ],
                                        ],
                                        "dispenseRequest" => [
                                            "dispenseInterval" => [
                                                "value" => $nonRacikan[$j]['konsumsi_perhari'],
                                                "unit" => "days",
                                                "system" => "http://unitsofmeasure.org",
                                                "code" => "d",
                                            ],
                                            "validityPeriod" => [
                                                "start" => Carbon::parse($tgl_selesai)->toIso8601String(),
                                                "end" => Carbon::parse($tglObatHabis)->toIso8601String(),
                                            ],
                                            // "numberOfRepeatsAllowed" => 0,
                                            // "quantity" => [
                                            //     "value" => 120,
                                            //     "unit" => "TAB",
                                            //     "system" =>
                                            //         "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            //     "code" => "TAB",
                                            // ],
                                            "expectedSupplyDuration" => [
                                                "value" => $pembagian,
                                                "unit" => "days",
                                                "system" => "http://unitsofmeasure.org",
                                                "code" => "d",
                                            ],
                                            "performer" => ["reference" => "Organization/".$organization_id],
                                        ],
                                    ],
                                    "request" => ["method" => "POST", "url" => "MedicationRequest"],
                                ];


                                // Medication For Dispense
                                $medicationForDispense_id = Str::uuid();
                                
                                $medicationForDispense =
                                [
                                    "fullUrl" => "urn:uuid:".$medicationForDispense_id,
                                    "resource" => 
                                    [
                                        "resourceType" => "Medication",
                                        "meta" => [
                                            "profile" => [
                                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                                            ],
                                        ],
                                        "identifier" => [
                                            [
                                                "system" =>
                                                    "http://sys-ids.kemkes.go.id/medication/".$organization_id,
                                                "use" => "official",
                                                "value" => $d
                                            ],
                                        ],
                                        "code" => [
                                            "coding" => [
                                                [
                                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                                    "code" => $kode_kfa,
                                                    "display" => $display,
                                                    // "display" => "Obat Anti Tuberculosis / Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Kaplet Salut Selaput (KIMIA FARMA)",
                                                ],
                                            ],
                                        ],
                                        "status" => "active",
                                        "manufacturer" => ["reference" => "Organization/".$organization_id],
                                        "form" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                                    "code" => $dosage_form,
                                                    "display" => $dosage_form_display,
                                                ],
                                            ],
                                        ],

                                        // khusus racikan
                                        // "ingredient" => [
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000330",
                                        //                     "display" => "Rifampin",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 150,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000328",
                                        //                     "display" => "Isoniazid",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 75,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000329",
                                        //                     "display" => "Pyrazinamide",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 400,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        //     [
                                        //         "itemCodeableConcept" => [
                                        //             "coding" => [
                                        //                 [
                                        //                     "system" =>
                                        //                         "http://sys-ids.kemkes.go.id/kfa",
                                        //                     "code" => "91000288",
                                        //                     "display" => "Ethambutol",
                                        //                 ],
                                        //             ],
                                        //         ],
                                        //         "isActive" => true,
                                        //         "strength" => [
                                        //             "numerator" => [
                                        //                 "value" => 275,
                                        //                 "system" => "http://unitsofmeasure.org",
                                        //                 "code" => "mg",
                                        //             ],
                                        //             "denominator" => [
                                        //                 "value" => 1,
                                        //                 "system" =>
                                        //                     "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //                 "code" => "TAB",
                                        //             ],
                                        //         ],
                                        //     ],
                                        // ],
                                        "extension" => [
                                            [
                                                "url" =>
                                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                                "valueCodeableConcept" => [
                                                    "coding" => [
                                                        [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                                            "code" => "NC",
                                                            "display" => "Non-compound",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    "request" => ["method" => "POST", "url" => "Medication"],
                                ];

                                // medicationDispense;
                                
                                
                                $medicationDispense =
                                [
                                    "fullUrl" => "urn:uuid:".self::generateUuid(),
                                    "resource" => [
                                        "resourceType" => "MedicationDispense",
                                        "identifier" => [
                                            [
                                                "use" => "official",
                                                "system" =>
                                                    "http://sys-ids.kemkes.go.id/prescription/".$organization_id,
                                                "value" => $eee
                                            ],
                                            [
                                                "use" => "official",
                                                "system" =>
                                                    "http://sys-ids.kemkes.go.id/prescription-item/".$organization_id,
                                                "value" => $f
                                            ],
                                        ],
                                        "status" => "completed",
                                        "category" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                                    "code" => "outpatient",
                                                    "display" => "Outpatient",
                                                ],
                                            ],
                                        ],
                                        "medicationReference" => [
                                            "reference" => "Medication/".$medicationForDispense_id,
                                            "display" => $display,
                                        ],
                                        "subject" => [
                                            "reference" => "Patient/".$pasien_uuid,
                                            "display" => $request->nama,
                                        ],
                                        "context" => ["reference" => "Encounter/".$encounter],
                                        "performer" => [
                                            [
                                                "actor" => [
                                                    "reference" => "Practitioner/".$apoteker_uuid,
                                                    "display" => $nama_apoteker,
                                                ],
                                            ],
                                        ],
                                        "location" => [
                                            "reference" => "Location/".$locationFarmasiRajal,
                                            "display" => $displayFarmasiRajal,
                                        ],
                                        "authorizingPrescription" => [
                                            [
                                                "reference" => "MedicationRequest/".$medicationRequest_id
                                            ],
                                        ],
                                        // "quantity" => [
                                        //     "value" => 120,
                                        //     "system" =>
                                        //         "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        //     "code" => "TAB",
                                        // ],
                                        "daysSupply" => [
                                            "value" => $nonRacikan[$j]['konsumsi_perhari'],
                                            "unit" => "Day",
                                            "system" => "http://unitsofmeasure.org",
                                            "code" => "d",
                                        ],
                                        "whenPrepared" => Carbon::parse($tgl_kirim)->toIso8601String(), // ini otw tgl diterima
                                        "whenHandedOver" => Carbon::parse($tgl_selesai)->toIso8601String(),
                                        "dosageInstruction" => [
                                            [
                                                "sequence" => 1,
                                                // "additionalInstruction" => [
                                                //     [
                                                //         "coding" => [
                                                //             [
                                                //                 "system" => "http://snomed.info/sct",
                                                //                 "code" => "418577003",
                                                //                 "display" =>
                                                //                     "Take at regular intervals. Complete the prescribed course unless otherwise directed",
                                                //             ],
                                                //         ],
                                                //     ],
                                                // ],
                                                "patientInstruction" => $nonRacikan[$j]['aturan']." ".$nonRacikan[$j]['keterangan'],
                                                "timing" => [
                                                    "repeat" => [
                                                        "frequency" => $nonRacikan[$j]['konsumsi_perhari'],
                                                        "period" => 1,
                                                        "periodUnit" => "d",
                                                    ],
                                                ],
                                                // "doseAndRate" => [
                                                //     [
                                                //         "type" => [
                                                //             "coding" => [
                                                //                 [
                                                //                     "system" =>
                                                //                         "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                //                     "code" => "ordered",
                                                //                     "display" => "Ordered",
                                                //                 ],
                                                //             ],
                                                //         ],
                                                //         "doseQuantity" => [
                                                //             "value" => 4,
                                                //             "unit" => "TAB",
                                                //             "system" =>
                                                //                 "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                                //             "code" => "TAB",
                                                //         ],
                                                //     ],
                                                // ],
                                            ],
                                        ],
                                    ],
                                    "request" => ["method" => "POST", "url" => "MedicationDispense"],
                                ];

                            }
                            

                            if ($longTerm) {
                                array_push($medicationForRequest['resource'],$tambahan);
                                array_push($medicationForDispense['resource'],$tambahan);
                            }
                            // if ($longTerm) {
                            //     array_push($medication[1]['resource'],$tambahan);
                            // }

                            $kirimObatNonRacikan[] = [
                                'medication' => $medicationForRequest ?? null,
                                'medication_request' => $medicationRequest ?? null,
                                'medicationD' => $medicationForDispense ?? null,
                                'medication_dispense' => $medicationDispense ?? null,
                            ];
                            // $preview[]= [
                            //     'medication' => $medication_id,
                            //     'medication_request' => $medicationRequest_id,
                            //     'medication_dispense' => $medicationForDispense_id
                            // ];
                        }
                    }
                }
            }

        }

        // return $preview;

        $data = [
            'racikan' => $kirimObatRacikan,
            'nonracikan' => $kirimObatNonRacikan,
        ];


        return $data;
        
        
    }
    static function diet($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
        $diet = count($request->diet) > 0 ? $request->diet[0] : null;
        $data = null;
        if ($diet) {
            $data =
            [
                "fullUrl" => "urn:uuid:".self::generateUuid(),
                "resource" => 
                [
                    "resourceType" => "Composition",
                    "identifier" => [
                        "system" => "http://sys-ids.kemkes.go.id/composition/".$organization_id,
                        "value" => $diet['Id'].'-'.$diet['diet'],
                    ],
                    "status" => "final",
                    "type" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "18842-5",
                                "display" => "Discharge summary",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "LP173421-1",
                                    "display" => "Report",
                                ],
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                    "encounter" => [
                        "reference" => "Encounter/".$encounter,
                        "display" => "Kunjungan  di hari ".$tgl_kunjungan,
                    ],
                    "date" => Carbon::parse($diet['tgl'])->toIso8601String(),
                    "author" => [["reference" => "Practitioner/".$practitioner_uuid, "display" => $nama_practitioner]],
                    "title" => "Resume Medis Rawat Jalan",
                    "custodian" => ["reference" => "Organization/".$organization_id],
                    "section" => [
                        [
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://loinc.org",
                                        "code" => "42344-2",
                                        "display" => "Discharge diet (narrative)",
                                    ],
                                ],
                            ],
                            "text" => [
                                "status" => "additional",
                                "div" => $diet['assessmen'],
                            ],
                        ],
                    ],
                    
                ],
                "request" => ["method" => "POST", "url" => "Composition"],
            ];
        }

        


        return $data;
        
        
    }

    static function telaah($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $telaah = $request->telaahresep;
        // return $telaah['petugas']['satset_uuid'];
        $data = null;
        if ($telaah) {
            if (!$telaah['petugas']) {
                $data=null;
            }

            $items=[];

            foreach ($telaah['administrasi'] as $key => $value) {
                $det=
                    [
                        "linkId" => $value['kode'],
                        "text" => $value['question'],
                        "answer" => [
                            [
                                "valueCoding" => [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                    "code" => "OV000052",
                                    "display" => $value['value'],
                                ],
                            ],
                        ],
                    ];
                array_push($items,$det);
            }

            $data =
            [
                "fullUrl" => "urn:uuid:".self::generateUuid(),
                "resource" => [
                    "resourceType" => "QuestionnaireResponse",
                    "questionnaire" =>
                        "https://fhir.kemkes.go.id/Questionnaire/Q0007",
                    "status" => "completed",
                    "subject" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                    "encounter" => ["reference" => "urn:uuid:".$encounter],
                    "authored" => Carbon::parse($telaah['created_at'])->toIso8601String(),
                    "author" => [
                        "reference" => "Practitioner/".$telaah['petugas']['satset_uuid'],
                        "display" => $telaah['petugas']['nama'],
                    ],
                    "source" => ["reference" => "Patient/".$pasien_uuid],
                    "item" => [
                        [
                            "linkId" => "1",
                            "text" => "Persyaratan Administrasi",
                            'item'=> $items,

                            // "item" => 
                            // [
                            //     [
                            //         "linkId" => "1.1",
                            //         "text" =>
                            //             "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
                            //         "answer" => [
                            //             [
                            //                 "valueCoding" => [
                            //                     "system" =>
                            //                         "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                     "code" => "OV000052",
                            //                     "display" => "Sesuai",
                            //                 ],
                            //             ],
                            //         ],
                            //     ],
                            //     [
                            //         "linkId" => "1.2",
                            //         "text" =>
                            //             "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
                            //         "answer" => [
                            //             [
                            //                 "valueCoding" => [
                            //                     "system" =>
                            //                         "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                     "code" => "OV000052",
                            //                     "display" => "Sesuai",
                            //                 ],
                            //             ],
                            //         ],
                            //     ],
                            //     [
                            //         "linkId" => "1.3",
                            //         "text" => "Apakah tanggal resep sudah sesuai?",
                            //         "answer" => [
                            //             [
                            //                 "valueCoding" => [
                            //                     "system" =>
                            //                         "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                     "code" => "OV000052",
                            //                     "display" => "Sesuai",
                            //                 ],
                            //             ],
                            //         ],
                            //     ],
                            //     [
                            //         "linkId" => "1.4",
                            //         "text" =>
                            //             "Apakah ruangan/unit asal resep sudah sesuai?",
                            //         "answer" => [
                            //             [
                            //                 "valueCoding" => [
                            //                     "system" =>
                            //                         "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                     "code" => "OV000052",
                            //                     "display" => "Sesuai",
                            //                 ],
                            //             ],
                            //         ],
                            //     ],
                            //     [
                            //         "linkId" => "2",
                            //         "text" => "Persyaratan Farmasetik",
                            //         "item" => [
                            //             [
                            //                 "linkId" => "2.1",
                            //                 "text" =>
                            //                     "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
                            //                 "answer" => [
                            //                     [
                            //                         "valueCoding" => [
                            //                             "system" =>
                            //                                 "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                             "code" => "OV000052",
                            //                             "display" => "Sesuai",
                            //                         ],
                            //                     ],
                            //                 ],
                            //             ],
                            //             [
                            //                 "linkId" => "2.2",
                            //                 "text" =>
                            //                     "Apakah dosis dan jumlah obat sudah sesuai?",
                            //                 "answer" => [
                            //                     [
                            //                         "valueCoding" => [
                            //                             "system" =>
                            //                                 "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                             "code" => "OV000052",
                            //                             "display" => "Sesuai",
                            //                         ],
                            //                     ],
                            //                 ],
                            //             ],
                            //             [
                            //                 "linkId" => "2.3",
                            //                 "text" =>
                            //                     "Apakah stabilitas obat sudah sesuai?",
                            //                 "answer" => [
                            //                     [
                            //                         "valueCoding" => [
                            //                             "system" =>
                            //                                 "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                             "code" => "OV000052",
                            //                             "display" => "Sesuai",
                            //                         ],
                            //                     ],
                            //                 ],
                            //             ],
                            //             [
                            //                 "linkId" => "2.4",
                            //                 "text" =>
                            //                     "Apakah aturan dan cara penggunaan obat sudah sesuai?",
                            //                 "answer" => [
                            //                     [
                            //                         "valueCoding" => [
                            //                             "system" =>
                            //                                 "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                             "code" => "OV000052",
                            //                             "display" => "Sesuai",
                            //                         ],
                            //                     ],
                            //                 ],
                            //             ],
                            //         ],
                            //     ],
                            //     [
                            //         "linkId" => "3",
                            //         "text" => "Persyaratan Klinis",
                            //         "item" => [
                            //             [
                            //                 "linkId" => "3.1",
                            //                 "text" =>
                            //                     "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
                            //                 "answer" => [
                            //                     [
                            //                         "valueCoding" => [
                            //                             "system" =>
                            //                                 "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            //                             "code" => "OV000052",
                            //                             "display" => "Sesuai",
                            //                         ],
                            //                     ],
                            //                 ],
                            //             ],
                            //             [
                            //                 "linkId" => "3.2",
                            //                 "text" =>
                            //                     "Apakah terdapat duplikasi pengobatan?",
                            //                 "answer" => [["valueBoolean" => false]],
                            //             ],
                            //             [
                            //                 "linkId" => "3.3",
                            //                 "text" =>
                            //                     "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
                            //                 "answer" => [["valueBoolean" => false]],
                            //             ],
                            //             [
                            //                 "linkId" => "3.4",
                            //                 "text" =>
                            //                     "Apakah terdapat kontraindikasi pengobatan?",
                            //                 "answer" => [["valueBoolean" => false]],
                            //             ],
                            //             [
                            //                 "linkId" => "3.5",
                            //                 "text" =>
                            //                     "Apakah terdapat dampak interaksi obat?",
                            //                 "answer" => [["valueBoolean" => false]],
                            //             ],
                            //         ],
                            //     ],
                            // ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "QuestionnaireResponse"],
            ];




        }

        return $data;
    }
    static function riwayatPengobatan($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $pasien = $request->norm;

        // $resepKeluar = Resepkeluarheder::select('norm','noresep','created_at')
        //     ->where('noreg', '!=', $request->noreg)->where('norm', $pasien)
        //     ->whereIn('flag', ['3', '4'])
        //     ->with([
        //         'rincian' => function ($ri) {
        //                 $ri->select(
        //                     'resep_keluar_r.kdobat',
        //                     'resep_keluar_r.noresep',
        //                     'resep_keluar_r.jumlah',
        //                     'resep_keluar_r.aturan', // signa
        //                     'resep_keluar_r.konsumsi', // signa
        //                     'resep_keluar_r.keterangan', // signa
        //                     'retur_penjualan_r.jumlah_retur',
        //                     'signa.jumlah as konsumsi_perhari',
        //                     DB::raw('
        //                     CASE
        //                     WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
        //                     ELSE resep_keluar_r.jumlah
        //                     END as qty
        //                     ') // iki jumlah obat sing non racikan mas..
        //                 )
        //                     ->leftJoin('retur_penjualan_r', function ($jo) {
        //                         $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
        //                             ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
        //                     })
        //                     ->leftJoin('signa', 'signa.signa', '=', 'resep_keluar_r.aturan')
        //                     ->with([
        //                         'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
        //                         // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
        //                         // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
        //                     ]);
        //             }
        //     ])
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        // return $resepKeluar;


        $token = AuthSatsetHelper::accessToken();
      
        $params = '/MedicationDispense?subject='.$pasien_uuid; 

        $send = BridgingSatsetHelper::get_data_nosave($token, $params);
        



        $data = null;
        if ($send['total'] > 0) {
            // if (!$telaah['petugas']) {
            //     $data=null;
            // }

            // $items=[];

            // return $send['entry'];

            $data=[];

            foreach ($send['entry'] as $key => $value) {
                // return $value['resource'];
                $xx =
                [
                    "fullUrl" => "urn:uuid:".self::generateUuid(),
                    "resource" => [
                        "resourceType" => "MedicationStatement",
                        // "contained" => [
                        //     [
                        //         "code" => [
                        //             "coding" => [
                        //                 [
                        //                     "code" => "93002313",
                        //                     "display" => "Paracetamol 500 mg Tablet (PAMOL)",
                        //                     "system" => "http://sys-ids.kemkes.go.id/kfa",
                        //                 ],
                        //             ],
                        //         ],
                        //         "extension" => [
                        //             [
                        //                 "url" =>
                        //                     "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                        //                 "valueCodeableConcept" => [
                        //                     "coding" => [
                        //                         [
                        //                             "code" => "NC",
                        //                             "display" => "Non-compound",
                        //                             "system" =>
                        //                                 "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                        //                         ],
                        //                     ],
                        //                 ],
                        //             ],
                        //         ],
                        //         "form" => [
                        //             "coding" => [
                        //                 [
                        //                     "code" => "BS066",
                        //                     "display" => "Tablet",
                        //                     "system" =>
                        //                         "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                        //                 ],
                        //             ],
                        //         ],
                        //         "id" => "2024070141486-med001",
                        //         "identifier" => [
                        //             [
                        //                 "system" =>
                        //                     "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                        //                 "use" => "official",
                        //                 "value" => "2024070141486-med001",
                        //             ],
                        //         ],
                        //         "ingredient" => [
                        //             [
                        //                 "isActive" => true,
                        //                 "itemCodeableConcept" => [
                        //                     "coding" => [
                        //                         [
                        //                             "code" => "91000101",
                        //                             "display" => "Paracetamol",
                        //                             "system" => "http://sys-ids.kemkes.go.id/kfa",
                        //                         ],
                        //                     ],
                        //                 ],
                        //                 "strength" => [
                        //                     "denominator" => [
                        //                         "code" => "TAB",
                        //                         "system" =>
                        //                             "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                        //                         "unit" => "Tablet",
                        //                         "value" => 1,
                        //                     ],
                        //                     "numerator" => [
                        //                         "code" => "mg",
                        //                         "system" => "http://unitsofmeasure.org",
                        //                         "value" => 500,
                        //                     ],
                        //                 ],
                        //             ],
                        //         ],
                        //         "batch" => [
                        //             "lotNumber" => "1625042A",
                        //             "expirationDate" => "2025-07-28",
                        //         ],
                        //         "meta" => [
                        //             "profile" => [
                        //                 "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                        //             ],
                        //         ],
                        //         "resourceType" => "Medication",
                        //         "status" => "active",
                        //     ],
                        // ],
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/medication-statement-category",
                                    "code" => "community",
                                    "display" => "Community",
                                ],
                            ],
                        ],
                        "medicationReference" => [
                            "reference" => $value['resource']['medicationReference']['reference'],
                            "display" => $value['resource']['medicationReference']['display'],
                        ],
                        "subject" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                        // "dosage" => [
                        //     [
                        //         "text" => "Parasetamol 500 mg diminum 3x sehari",
                        //         "timing" => [
                        //             "repeat" => [
                        //                 "frequency" => 3,
                        //                 "period" => 1,
                        //                 "periodUnit" => "d",
                        //             ],
                        //         ],
                        //     ],
                        // ],
                        // "effectiveDateTime" => "2023-01-23T18:00:00+00:00",
                        // "dateAsserted" => "2023-06-04T05:40:00+00:00",
                        "informationSource" => ["reference" => "Patient/".$pasien_uuid, "display" => $request->nama],
                        "context" => ["reference" => "Encounter/".$encounter],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationStatement"],
                ];

                array_push($data, $xx);
            }

        }

        return $data;
    }

   

    public function ContohBundleRajal()
    {
        $arrayVar = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => [

                // "Encounter"
                [
                    "fullUrl" => "urn:uuid:",
                    "resource" => [
                        "resourceType" => "Encounter",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/encounter/{{Org_ID}}",
                                "value" => "",
                            ],
                        ],
                        "status" => "finished",
                        "statusHistory" => [
                            [
                                "status" => "arrived",
                                "period" => [
                                    "start" => "2023-08-31T00:00:00+00:00",
                                    "end" => "2023-08-31T01:00:00+00:00",
                                ],
                            ],
                            [
                                "status" => "in-progress",
                                "period" => [
                                    "start" => "2023-08-31T01:00:00+00:00",
                                    "end" => "2023-08-31T04:05:00+00:00",
                                ],
                            ],
                            [
                                "status" => "finished",
                                "period" => [
                                    "start" => "2023-08-31T04:05:00+00:00",
                                    "end" => "2023-08-31T04:10:00+00:00",
                                ],
                            ],
                        ],
                        "class" => [
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                            "code" => "AMB",
                            "display" => "ambulatory",
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "participant" => [
                            [
                                "type" => [
                                    [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                                "code" => "ATND",
                                                "display" => "attender",
                                            ],
                                        ],
                                    ],
                                ],
                                "individual" => [
                                    "reference" => "Practitioner/",
                                    "display" => "",
                                ],
                            ],
                        ],
                        "period" => [
                            "start" => "2023-08-31T00:00:00+00:00",
                            "end" => "2023-08-31T02:00:00+00:00",
                        ],
                        "diagnosis" => [
                            [
                                "condition" => [
                                    "reference" =>
                                        "urn:uuid:{{Condition_DiagnosisPrimer}}",
                                    "display" => "{{DiagnosisPrimer_Text}}",
                                ],
                                "use" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                            "code" => "DD",
                                            "display" => "Discharge diagnosis",
                                        ],
                                    ],
                                ],
                                "rank" => 1,
                            ],
                            [
                                "condition" => [
                                    "reference" =>
                                        "urn:uuid:{{Condition_DiagnosisSekunder}}",
                                    "display" => "{{DiagnosisSekunder_Text}}",
                                ],
                                "use" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                            "code" => "DD",
                                            "display" => "Discharge diagnosis",
                                        ],
                                    ],
                                ],
                                "rank" => 2,
                            ],
                        ],
                        "hospitalization" => [
                            "dischargeDisposition" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                        "code" => "oth",
                                        "display" => "other-hcf",
                                    ],
                                ],
                                "text" =>
                                    "Rujukan ke RSUP Fatmawati dengan nomor rujukan {{No_Rujukan_Pasien}}",
                            ],
                        ],
                        "location" => [
                            [
                                "extension" => [
                                    [
                                        "extension" => [
                                            [
                                                "url" => "value",
                                                "valueCodeableConcept" => [
                                                    "coding" => [
                                                        [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                            "code" => "reguler",
                                                            "display" =>
                                                                "Kelas Reguler",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "url" => "upgradeClassIndicator",
                                                "valueCodeableConcept" => [
                                                    "coding" => [
                                                        [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                            "code" => "kelas-tetap",
                                                            "display" =>
                                                                "Kelas Tetap Perawatan",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        "url" =>
                                            "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                                    ],
                                ],
                                "location" => [
                                    "reference" => "Location/{{Location_Poli_id}}",
                                    "display" => "",
                                ],
                                "period" => [
                                    "start" => "2023-08-31T00:00:00+00:00",
                                    "end" => "2023-08-31T02:00:00+00:00",
                                ],
                            ],
                        ],
                        "serviceProvider" => ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "request" => ["method" => "POST", "url" => "Encounter"],
                ],

                // "Condition"
                [
                    "fullUrl" => "urn:uuid:c566d6e2-4da0-4895-9bcb-8051dd16548c",
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active",
                                ],
                            ],
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "problem-list-item",
                                        "display" => "Problem List Item",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "16932000",
                                    "display" => "Batuk darah",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "onsetDateTime" => "2023-02-02T00:00:00+00:00",
                        "recordedDate" => "2023-08-31T01:00:00+00:00",
                        "recorder" => ["reference" => "Practitioner/", "display" => ""],
                        "note" => [["text" => "Batuk Berdarah sejak 3bl yll"]],
                    ],
                    "request" => ["method" => "POST", "url" => "Condition"],
                ],

                // "Observation_Nadi"
                [
                    "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
                    "resource" => [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "vital-signs",
                                        "display" => "Vital Signs",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "8867-4",
                                    "display" => "Heart rate",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "effectiveDateTime" => "2023-08-31T01:10:00+00:00",
                        "issued" => "2023-08-31T01:10:00+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/", "display" => ""],
                        ],
                        "valueQuantity" => [
                            "value" => 80,
                            "unit" => "{beats}/min",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "{beats}/min",
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],

                // "Observation_Kesadaran"
                [
                    "fullUrl" => "urn:uuid:{{Observation_Kesadaran}}",
                    "resource" => [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "vital-signs",
                                        "display" => "Vital Signs",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "67775-7",
                                    "display" => "Level of responsiveness",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "effectiveDateTime" => "2023-08-31T01:10:00+00:00",
                        "issued" => "2023-08-31T01:10:00+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/", "display" => ""],
                        ],
                        "valueCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "248234008",
                                    "display" => "Mentally alert",
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],

                // "CarePlan_RencanaRawat"
                [
                    "fullUrl" => "urn:uuid:{{CarePlan_RencanaRawat}}",
                    "resource" => [
                        "resourceType" => "CarePlan",
                        "status" => "active",
                        "intent" => "plan",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "736271009",
                                        "display" => "Outpatient care plan",
                                    ],
                                ],
                            ],
                        ],
                        "title" => "Rencana Rawat Pasien",
                        "description" => "Rencana Rawat Pasien",
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "created" => "2023-08-31T01:20:00+00:00",
                        "author" => ["reference" => "Practitioner/", "display" => ""],
                    ],
                    "request" => ["method" => "POST", "url" => "CarePlan"],
                ],

                // "CarePlan_Instruksi"
                [
                    "fullUrl" => "urn:uuid:{{CarePlan_Instruksi}}",
                    "resource" => [
                        "resourceType" => "CarePlan",
                        "status" => "active",
                        "intent" => "plan",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "736271009",
                                        "display" => "Outpatient care plan",
                                    ],
                                ],
                            ],
                        ],
                        "title" => "Instruksi Medik dan Keperawatan Pasien",
                        "description" =>
                            "Penanganan TB Pasien dilakukan dengan pemberian pengobatan TB.",
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "created" => "2023-08-31T01:20:00+00:00",
                        "author" => ["reference" => "Practitioner/"],
                    ],
                    "request" => ["method" => "POST", "url" => "CarePlan"],
                ],

                // "Procedure_PraRad"
                [
                    "fullUrl" => "urn:uuid:{{Procedure_PraRad}}",
                    "resource" => [
                        "resourceType" => "Procedure",
                        "status" => "not-done",
                        "category" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "103693007",
                                    "display" => "Diagnostic procedure",
                                ],
                            ],
                            "text" => "Prosedur diagnostik",
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "792805006",
                                    "display" => "Fasting",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "performedPeriod" => [
                            "start" => "2023-07-04T09:30:00+00:00",
                            "end" => "2023-07-04T09:30:00+00:00",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/",
                                    "display" => "",
                                ],
                            ],
                        ],
                        "note" => [
                            ["text" => "Tidak puasa sebelum pemeriksaan radiologi"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Procedure"],
                ],

                // "Observation_PraRad"
                [
                    "fullUrl" => "urn:uuid:{{Observation_PraRad}}",
                    "resource" => [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "survey",
                                        "display" => "Survey",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "82810-3",
                                    "display" => "Pregnancy status",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/"],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" => "Kunjungan  4 Juli 2023",
                        ],
                        "effectiveDateTime" => "2023-07-04T09:30:00+00:00",
                        "issued" => "2023-07-04T09:30:00+00:00",
                        "performer" => [["reference" => "Practitioner/"]],
                        "valueCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "60001007",
                                    "display" => "Not pregnant",
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],

                // "AllergyIntolerance_PraRad"
                [
                    "fullUrl" => "urn:uuid:{{AllergyIntolerance_PraRad}}",
                    "resource" => [
                        "resourceType" => "AllergyIntolerance",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/allergy/{{Org_ID}}",
                                "value" => "P20240001",
                            ],
                        ],
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                    "code" => "active",
                                    "display" => "Active",
                                ],
                            ],
                        ],
                        "verificationStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                    "code" => "confirmed",
                                    "display" => "Confirmed",
                                ],
                            ],
                        ],
                        "category" => ["medication"],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "91000928",
                                    "display" => "Barium Sulfate",
                                ],
                            ],
                            "text" => "Alergi Barium Sulfate",
                        ],
                        "patient" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" => "Kunjungan  4 Juli 2023",
                        ],
                        "recordedDate" => "2023-07-04T09:30:00+00:00",
                        "recorder" => ["reference" => "Practitioner/"],
                    ],
                    "request" => ["method" => "POST", "url" => "AllergyIntolerance"],
                ],

                // "ServiceRequest_PraRad"
                [
                    "fullUrl" => "urn:uuid:",
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
                                "value" => "",
                            ],
                            [
                                "use" => "usual",
                                "type" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v2-0203",
                                            "code" => "ACSN",
                                        ],
                                    ],
                                ],
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/acsn/{{Org_ID}}",
                                "value" => "",
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "363679005",
                                        "display" => "Imaging",
                                    ],
                                ],
                            ],
                        ],
                        "priority" => "routine",
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "24648-8",
                                    "display" => "XR Chest PA upr",
                                ],
                            ],
                            "text" => "Pemeriksaan CXR PA",
                        ],
                        "orderDetail" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://dicom.nema.org/resources/ontology/DCM",
                                        "code" => "DX",
                                    ],
                                ],
                                "text" => "Modality Code: DX",
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/ae-title",
                                        "display" => "XR0001",
                                    ],
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/"],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "occurrenceDateTime" => "2023-08-31T02:05:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/",
                            "display" => "",
                        ],
                        "performer" => [
                            [
                                "reference" => "Practitioner/10012572188",
                                "display" => "Dokter Radiologist",
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "text" =>
                                    "Permintaan pemeriksaan CXR PA untuk tuberculosis",
                            ],
                        ],
                        "supportingInfo" => [
                            ["reference" => "urn:uuid:{{Observation_PraRad}}"],
                            ["reference" => "urn:uuid:{{Procedure_PraRad}}"],
                            ["reference" => "urn:uuid:{{AllergyIntolerance_PraRad}}"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ],

                // "Observation_PraRad"
                [
                    "fullUrl" => "urn:uuid:",
                    "resource" => [
                        "resourceType" => "Observation",
                        "basedOn" => [["reference" => "urn:uuid:"]],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "imaging",
                                        "display" => "Imaging",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "24648-8",
                                    "display" => "XR Chest PA upr",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "effectiveDateTime" => "2023-08-31T02:35:00+00:00",
                        "issued" => "2023-08-31T02:35:00+00:00",
                        "performer" => [
                            [
                                "reference" => "Practitioner/",
                                "display" => "Dokter Radiologist",
                            ],
                        ],
                        "valueString" => "Left upper and middle lung zones show reticulonodular opacities.
                                            The left apical lung zone shows a cavitary lesion( active TB).
                                            Left apical pleural thickening
                                            Mild mediastinum widening is noted
                                            Normal heart size.
                                            Free costophrenic angles.",
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],

                // "DiagnosticReport_PraRad"
                [
                    "fullUrl" => "urn:uuid:{{DiagnosticReport_Rad}}",
                    "resource" => [
                        "resourceType" => "DiagnosticReport",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/diagnostic/{{Org_ID}}/rad",
                                "value" => "52343522",
                            ],
                        ],
                        "basedOn" => [["reference" => "urn:uuid:"]],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0074",
                                        "code" => "RAD",
                                        "display" => "Radiology",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "24648-8",
                                    "display" => "XR Chest PA upr",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/"],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "effectiveDateTime" => "2023-08-31T05:00:00+00:00",
                        "issued" => "2023-08-31T05:00:00+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/"],
                            ["reference" => "Organization/{{Org_ID}}"],
                        ],
                        "result" => [["reference" => "urn:uuid:"]],
                        "imagingStudy" => [
                            [
                                "reference" =>
                                    "urn:uuid:354e1828-b094-493a-b393-2c18a28476ea",
                            ],
                        ],
                        "conclusion" => "Active Tuberculosis indicated",
                    ],
                    "request" => ["method" => "POST", "url" => "DiagnosticReport"],
                ],

                // "Procedure_Terapetik"
                [
                    "fullUrl" => "urn:uuid:{{Procedure_Terapetik}}",
                    "resource" => [
                        "resourceType" => "Procedure",
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "277132007",
                                    "display" => "Therapeutic procedure",
                                ],
                            ],
                            "text" => "Therapeutic procedure",
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                    "code" => "93.94",
                                    "display" =>
                                        "Respiratory medication administered by nebulizer",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" =>
                                "Tindakan Nebulisasi  pada Selasa tanggal 31 Agustus 2023",
                        ],
                        "performedPeriod" => [
                            "start" => "2023-08-31T02:27:00+00:00",
                            "end" => "2023-08-31T02:27:00+00:00",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/",
                                    "display" => "",
                                ],
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-10",
                                        "code" => "A15.0",
                                        "display" =>
                                            "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                    ],
                                ],
                            ],
                        ],
                        "bodySite" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "74101002",
                                        "display" => "Both lungs",
                                    ],
                                ],
                            ],
                        ],
                        "note" => [
                            ["text" => "Nebulisasi untuk melegakan sesak napas"],
                        ],
                        "usedCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91001164",
                                        "display" => "Nebulizer",
                                    ],
                                ],
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" => "sys-ids.kemkes.go.id/kfa",
                                        "code" => "93000453",
                                        "display" =>
                                            "Salbutamol 100 mcg Cairan Inhalasi (SUPRASMA)",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Procedure"],
                ],

                // "Procedure_Konseling"
                [
                    "fullUrl" => "urn:uuid:{{Procedure_Konseling}}",
                    "resource" => [
                        "resourceType" => "Procedure",
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "409063005",
                                    "display" => "Counselling",
                                ],
                            ],
                            "text" => "Counselling",
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                    "code" => "94.4",
                                    "display" => "Other psychotherapy and counselling",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" =>
                                "Konseling  pada Selasa tanggal 31 Agustus 2023",
                        ],
                        "performedPeriod" => [
                            "start" => "2023-08-31T02:27:00+00:00",
                            "end" => "2023-08-31T02:27:00+00:00",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/",
                                    "display" => "",
                                ],
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-10",
                                        "code" => "A15.0",
                                        "display" =>
                                            "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                    ],
                                ],
                            ],
                        ],
                        "note" => [
                            [
                                "text" =>
                                    "Konseling keresahan pasien karena diagnosis TB",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Procedure"],
                ],

                // "Condition_DiagnosisPrimer"
                [
                    "fullUrl" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active",
                                ],
                            ],
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "encounter-diagnosis",
                                        "display" => "Encounter Diagnosis",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "A15.0",
                                    "display" =>
                                        "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "onsetDateTime" => "2023-08-31T04:10:00+00:00",
                        "recordedDate" => "2023-08-31T04:10:00+00:00",
                    ],
                    "request" => ["method" => "POST", "url" => "Condition"],
                ],

                // "Condition_DiagnosisSekunder"
                [
                    "fullUrl" => "urn:uuid:{{Condition_DiagnosisSekunder}}",
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active",
                                ],
                            ],
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "encounter-diagnosis",
                                        "display" => "Encounter Diagnosis",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "E11.9",
                                    "display" =>
                                        "Type 2 diabetes mellitus, Type 2 diabetes mellitus",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" => "Kunjungan  di hari Kamis, 31 Agustus 2023",
                        ],
                        "onsetDateTime" => "2023-08-31T04:10:00+00:00",
                        "recordedDate" => "2023-08-31T04:10:00+00:00",
                    ],
                    "request" => ["method" => "POST", "url" => "Condition"],
                ],

                // "Procedure_Edukasi"
                [
                    "fullUrl" => "urn:uuid:{{Procedure_Edukasi}}",
                    "resource" => [
                        "resourceType" => "Procedure",
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "409073007",
                                    "display" => "Education",
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "61310001",
                                    "display" => "Nutrition education",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "performedPeriod" => [
                            "start" => "2023-08-31T03:30:00+00:00",
                            "end" => "2023-08-31T03:40:00+00:00",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/",
                                    "display" => "",
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Procedure"],
                ],

                // "Medication_forRequest"
                [
                    "fullUrl" => "urn:uuid:{{Medication_forRequest}}",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
                                "value" => "123456789",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93001019",
                                    "display" =>
                                        "Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Tablet Salut Selaput (KIMIA FARMA)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS023",
                                    "display" => "Kaplet Salut Selaput",
                                ],
                            ],
                        ],
                        "ingredient" => [
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000330",
                                            "display" => "Rifampin",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 150,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000328",
                                            "display" => "Isoniazid",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 75,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000329",
                                            "display" => "Pyrazinamide",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 400,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000288",
                                            "display" => "Ethambutol",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 275,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],

                // MedicationRequest
                [
                    "fullUrl" => "urn:uuid:",
                    "resource" => [
                        "resourceType" => "MedicationRequest",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
                                "value" => "123456788",
                            ],
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
                                "value" => "123456788-1",
                            ],
                        ],
                        "status" => "completed",
                        "intent" => "order",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                        "code" => "outpatient",
                                        "display" => "Outpatient",
                                    ],
                                ],
                            ],
                        ],
                        "priority" => "routine",
                        "medicationReference" => [
                            "reference" => "urn:uuid:{{Medication_forRequest}}",
                            "display" => "",
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "authoredOn" => "2023-08-31T03:27:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/",
                            "display" => "",
                        ],
                        "reasonReference" => [
                            [
                                "reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                                "display" => "{{DiagnosisPrimer_Text}}",
                            ],
                        ],
                        "courseOfTherapyType" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/medicationrequest-course-of-therapy",
                                    "code" => "continuous",
                                    "display" => "Continuing long term therapy",
                                ],
                            ],
                        ],
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "additionalInstruction" => [
                                    [
                                        "coding" => [
                                            [
                                                "system" => "http://snomed.info/sct",
                                                "code" => "418577003",
                                                "display" =>
                                                    "Take at regular intervals. Complete the prescribed course unless otherwise directed",
                                            ],
                                        ],
                                    ],
                                ],
                                "patientInstruction" =>
                                    "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 4,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "dispenseRequest" => [
                            "dispenseInterval" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "validityPeriod" => [
                                "start" => "2023-08-31T03:27:00+00:00",
                                "end" => "2024-07-22T14:27:00+00:00",
                            ],
                            "numberOfRepeatsAllowed" => 0,
                            "quantity" => [
                                "value" => 120,
                                "unit" => "TAB",
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                "code" => "TAB",
                            ],
                            "expectedSupplyDuration" => [
                                "value" => 30,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "performer" => ["reference" => "Organization/{{Org_ID}}"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationRequest"],
                ],

                // QuestionnaireResponse_KajianResep
                [
                    "fullUrl" => "urn:uuid:{{QuestionnaireResponse_KajianResep}}",
                    "resource" => [
                        "resourceType" => "QuestionnaireResponse",
                        "questionnaire" =>
                            "https://fhir.kemkes.go.id/Questionnaire/Q0007",
                        "status" => "completed",
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => ["reference" => "urn:uuid:"],
                        "authored" => "2023-08-31T03:00:00+00:00",
                        "author" => [
                            "reference" => "Practitioner/10009880728",
                            "display" => "Apoteker A",
                        ],
                        "source" => ["reference" => "Patient/"],
                        "item" => [
                            [
                                "linkId" => "1",
                                "text" => "Persyaratan Administrasi",
                                "item" => [
                                    [
                                        "linkId" => "1.1",
                                        "text" =>
                                            "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "1.2",
                                        "text" =>
                                            "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "1.3",
                                        "text" => "Apakah tanggal resep sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "1.4",
                                        "text" =>
                                            "Apakah ruangan/unit asal resep sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "2",
                                        "text" => "Persyaratan Farmasetik",
                                        "item" => [
                                            [
                                                "linkId" => "2.1",
                                                "text" =>
                                                    "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "2.2",
                                                "text" =>
                                                    "Apakah dosis dan jumlah obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "2.3",
                                                "text" =>
                                                    "Apakah stabilitas obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "2.4",
                                                "text" =>
                                                    "Apakah aturan dan cara penggunaan obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "3",
                                        "text" => "Persyaratan Klinis",
                                        "item" => [
                                            [
                                                "linkId" => "3.1",
                                                "text" =>
                                                    "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "3.2",
                                                "text" =>
                                                    "Apakah terdapat duplikasi pengobatan?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                            [
                                                "linkId" => "3.3",
                                                "text" =>
                                                    "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                            [
                                                "linkId" => "3.4",
                                                "text" =>
                                                    "Apakah terdapat kontraindikasi pengobatan?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                            [
                                                "linkId" => "3.5",
                                                "text" =>
                                                    "Apakah terdapat dampak interaksi obat?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "QuestionnaireResponse"],
                ],

                // Medication_forDispense
                [
                    "fullUrl" => "urn:uuid:{{Medication_forDispense}}",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
                                "value" => "123456789",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93001019",
                                    "display" =>
                                        "Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Tablet Salut Selaput (KIMIA FARMA)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS023",
                                    "display" => "Kaplet Salut Selaput",
                                ],
                            ],
                        ],
                        "ingredient" => [
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000330",
                                            "display" => "Rifampin",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 150,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000328",
                                            "display" => "Isoniazid",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 75,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000329",
                                            "display" => "Pyrazinamide",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 400,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://sys-ids.kemkes.go.id/kfa",
                                            "code" => "91000288",
                                            "display" => "Ethambutol",
                                        ],
                                    ],
                                ],
                                "isActive" => true,
                                "strength" => [
                                    "numerator" => [
                                        "value" => 275,
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mg",
                                    ],
                                    "denominator" => [
                                        "value" => 1,
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                        ],
                        "batch" => [
                            "lotNumber" => "1625042A",
                            "expirationDate" => "2025-07-22T14:27:00+00:00",
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],

                // MedicationDispense
                [
                    "fullUrl" => "urn:uuid:{{MedicationDispense_id}}",
                    "resource" => [
                        "resourceType" => "MedicationDispense",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
                                "value" => "123456788",
                            ],
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
                                "value" => "123456788-1",
                            ],
                        ],
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                    "code" => "outpatient",
                                    "display" => "Outpatient",
                                ],
                            ],
                        ],
                        "medicationReference" => [
                            "reference" => "urn:uuid:{{Medication_forDispense}}",
                            "display" => "",
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "context" => ["reference" => "urn:uuid:"],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/",
                                    "display" => "Apoteker Miller",
                                ],
                            ],
                        ],
                        "location" => [
                            "reference" => "Location/{{Location_farmasi_id}}",
                            "display" => "Farmasi",
                        ],
                        "authorizingPrescription" => [["reference" => "urn:uuid:"]],
                        "quantity" => [
                            "value" => 120,
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                            "code" => "TAB",
                        ],
                        "daysSupply" => [
                            "value" => 30,
                            "unit" => "Day",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "whenPrepared" => "2023-08-31T03:27:00+00:00",
                        "whenHandedOver" => "2023-08-31T03:27:00+00:00",
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "additionalInstruction" => [
                                    [
                                        "coding" => [
                                            [
                                                "system" => "http://snomed.info/sct",
                                                "code" => "418577003",
                                                "display" =>
                                                    "Take at regular intervals. Complete the prescribed course unless otherwise directed",
                                            ],
                                        ],
                                    ],
                                ],
                                "patientInstruction" =>
                                    "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 4,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationDispense"],
                ],

                // ClinicalImpression
                [
                    "fullUrl" => "urn:uuid:{{ClinicalImpression_Prognosis}}",
                    "resource" => [
                        "resourceType" => "ClinicalImpression",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/clinicalimpression/{{Org_ID}}",
                                "value" => "",
                            ],
                        ],
                        "status" => "completed",
                        "description" => " terdiagnosa TB, dan adanya DM-2",
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" => "Kunjungan  di hari Selasa, 31 Agustus 2023",
                        ],
                        "effectiveDateTime" => "2023-10-31T03:37:31+00:00",
                        "date" => "2023-10-31T03:15:31+00:00",
                        "assessor" => ["reference" => "Practitioner/"],
                        "problem" => [
                            ["reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}"],
                        ],
                        "investigation" => [
                            [
                                "code" => ["text" => "Pemeriksaan CXR PA"],
                                "item" => [
                                    [
                                        "reference" =>
                                            "urn:uuid:{{DiagnosticReport_Rad}}",
                                    ],
                                    ["reference" => "urn:uuid:"],
                                ],
                            ],
                        ],
                        "summary" =>
                            "Prognosis terhadap Tuberkulosis, disertai adanya riwayat Diabetes Mellitus tipe 2",
                        "finding" => [
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://hl7.org/fhir/sid/icd-10",
                                            "code" => "A15.0",
                                            "display" =>
                                                "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                        ],
                                    ],
                                ],
                                "itemReference" => [
                                    "reference" =>
                                        "urn:uuid:{{Condition_DiagnosisPrimer}}",
                                ],
                            ],
                            [
                                "itemCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://hl7.org/fhir/sid/icd-10",
                                            "code" => "E44.1",
                                            "display" =>
                                                "Mild protein-calorie malnutrition",
                                        ],
                                    ],
                                ],
                                "itemReference" => [
                                    "reference" =>
                                        "urn:uuid:{{Condition_DiagnosisSekunder}}",
                                ],
                            ],
                        ],
                        "prognosisCodeableConcept" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "170968001",
                                        "display" => "Prognosis good",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "ClinicalImpression"],
                ],

                // ServiceRequest
                [
                    "fullUrl" => "urn:uuid:",
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
                                "value" => "000012345",
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "3457005",
                                        "display" => "Patient referral",
                                    ],
                                ],
                            ],
                        ],
                        "priority" => "routine",
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "737481003",
                                    "display" => "Inpatient care management",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/"],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" => "Kunjungan  di hari Kamis, 31 Agustus 2023 ",
                        ],
                        "occurrenceDateTime" => "2023-08-31T04:25:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/",
                            "display" => "",
                        ],
                        "performer" => [
                            ["reference" => "Practitioner/", "display" => "Fatma"],
                        ],
                        "locationCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                        "code" => "HOSP",
                                        "display" => "Hospital",
                                    ],
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                        "code" => "AMB",
                                        "display" => "Ambulance",
                                    ],
                                ],
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-10",
                                        "code" => "A15.0",
                                        "display" =>
                                            "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                    ],
                                ],
                            ],
                        ],
                        "patientInstruction" =>
                            "Rujukan ke Rawat Inap RSUP Fatmawati. Dalam keadaan darurat dapat menghubungi hotline Fasyankes di nomor 14045",
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ],

                // Condition
                [
                    "fullUrl" => "urn:uuid:{{Condition_Stabil}}",
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active",
                                ],
                            ],
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "problem-list-item",
                                        "display" => "Problem List Item",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "359746009",
                                    "display" => "Patient\'s condition stable",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/", "display" => ""],
                        "encounter" => [
                            "reference" => "urn:uuid:",
                            "display" => "Kunjungan  di hari Kamis, 31 Agustus 2023",
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Condition"],
                ],
            ],
        ];
    }



    public function contoh()
    {
        
        $arrayVar = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => [
                [
                    "fullUrl" => "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                    "resource" => [
                        "resourceType" => "Encounter",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/encounter/10085103",
                                "value" => "123456789",
                            ],
                        ],
                        "status" => "finished",
                        "class" => [
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                            "code" => "IMP",
                            "display" => "inpatient encounter",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "participant" => [
                            [
                                "type" => [
                                    [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                                "code" => "ATND",
                                                "display" => "attender",
                                            ],
                                        ],
                                    ],
                                ],
                                "individual" => [
                                    "reference" => "Practitioner/N10000001",
                                    "display" => "Dokter Bronsig",
                                ],
                                "period" => [
                                    "start" => "2023-03-26T08:00:00+00:00",
                                    "end" => "2023-03-30T15:30:27+07:00",
                                ],
                            ],
                        ],
                        "period" => [
                            "start" => "2023-03-26T08:00:00+00:00",
                            "end" => "2023-03-30T15:30:27+07:00",
                        ],
                        "location" => [
                            [
                                "location" => [
                                    "reference" =>
                                        "Location/b29038d4-9ef0-4eb3-a2e9-3c02df668b07",
                                    "display" =>
                                        "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                                ],
                                "extension" => [
                                    [
                                        "url" =>
                                            "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                                        "extension" => [
                                            [
                                                "url" => "value",
                                                "valueCodeableConcept" => [
                                                    "coding" => [
                                                        [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Inpatient",
                                                            "code" => "1",
                                                            "display" => "Kelas 1",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "url" => "upgradeClassIndicator",
                                                "valueCodeableConcept" => [
                                                    "coding" => [
                                                        [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                            "code" => "kelas-tetap",
                                                            "display" =>
                                                                "Kelas Tetap Perawatan",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "diagnosis" => [
                            [
                                "condition" => [
                                    "reference" =>
                                        "Condition/e60551d9-7887-4ed4-bc4f-1a53e706d30f",
                                    "display" => "Chronic kidney disease, stage 5",
                                ],
                                "use" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                            "code" => "DD",
                                            "display" => "Discharge diagnosis",
                                        ],
                                    ],
                                ],
                                "rank" => 1,
                            ],
                            [
                                "condition" => [
                                    "reference" =>
                                        "Condition/2984c564-263d-4979-8cd0-07c72637edc9",
                                    "display" => "Anemia in chronic kidney disease",
                                ],
                                "use" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                            "code" => "DD",
                                            "display" => "Discharge diagnosis",
                                        ],
                                    ],
                                ],
                                "rank" => 2,
                            ],
                        ],
                        "statusHistory" => [
                            [
                                "status" => "in-progress",
                                "period" => [
                                    "start" => "2023-03-26T08:00:00+00:00",
                                    "end" => "2023-03-30T15:30:00+00:00",
                                ],
                            ],
                            [
                                "status" => "finished",
                                "period" => [
                                    "start" => "2023-03-30T15:30:00+00:00",
                                    "end" => "2023-03-30T15:30:00+00:00",
                                ],
                            ],
                        ],
                        "serviceProvider" => ["reference" => "Organization/10085103"],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:1e1a260d-538f-4172-ad68-0aa5f8ccfc4a",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Encounter"],
                ],
                [
                    "fullUrl" => "urn:uuid:15ffea7d-9171-4572-8d5f-246cf4cd4473",
                    "resource" => [
                        "resourceType" => "CarePlan",
                        "title" => "Rencana Rawat Pasien",
                        "status" => "active",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "736353004",
                                        "display" => " Inpatient care plan",
                                    ],
                                ],
                            ],
                        ],
                        "intent" => "plan",
                        "description" =>
                            "Pasien akan melakukan Pengecekan Kolesterol Darah dan Proses CT-Scan serta Tindakan Hemodialisis dengan Rencana Lama Waktu Rawat selama 3-4 Hari",
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "created" => "2023-03-27T08:00:00+00:00",
                        "author" => ["reference" => "Practitioner/N10000001"],
                    ],
                    "request" => ["method" => "POST", "url" => "CarePlan"],
                ],
                [
                    "fullUrl" => "urn:uuid:194c9b32-788b-4110-59ea-7161aa33cf68",
                    "resource" => [
                        "resourceType" => "CarePlan",
                        "title" => "Instruksi Medik dan Keperawatan Pasien",
                        "status" => "active",
                        "intent" => "plan",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "736353004",
                                        "display" => " Inpatient care plan",
                                    ],
                                ],
                            ],
                        ],
                        "description" =>
                            "Penanganan Anemia Pasien dilakukan dengan pemberian hormone eritropoitin, transfusi darah, dan vitamin.",
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "created" => "2023-03-27T08:00:00+00:00",
                        "author" => ["reference" => "Practitioner/N10000001"],
                    ],
                    "request" => ["method" => "POST", "url" => "CarePlan"],
                ],
                [
                    "fullUrl" => "urn:uuid:2a9d3ebd-2ff7-4f38-96c6-2f76e989720f",
                    "resource" => [
                        "resourceType" => "Procedure",
                        "status" => "not-done",
                        "category" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "103693007",
                                    "display" => "Diagnostic procedure",
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "792805006",
                                    "display" => "Fasting",
                                ],
                            ],
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "performedPeriod" => [
                            "start" => "2023-03-27T08:00:00+00:00",
                            "end" => "2023-03-27T08:00:00+00:00",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/N10000001",
                                    "display" => "Dokter Bronsig",
                                ],
                            ],
                        ],
                        "note" => [["text" => "Prosedur Puasa tidak dilakukan Pasien"]],
                    ],
                    "request" => ["method" => "POST", "url" => "Procedure"],
                ],
                [
                    "fullUrl" => "urn:uuid:196e33ca-6673-4ae0-acd9-1f03a95d7b32",
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/servicerequest/10085103",
                                "value" => "00001",
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "priority" => "routine",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "108252007",
                                        "display" => "Laboratory procedure",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "2093-3",
                                    "display" =>
                                        "Cholesterol [Mass/volume] in Serum or Plasma",
                                ],
                            ],
                            "text" => "Kolesterol Total",
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                            "display" =>
                                "Permintaan Pemeriksaan Kolesterol Total Jum\'at, 26 Maret 2023 pukul 09:30 WIB",
                        ],
                        "occurrenceDateTime" => "2023-03-28T16:30:00+00:00",
                        "authoredOn" => "2023-03-27T14:00:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/N10000001",
                            "display" => "Dokter Bronsig",
                        ],
                        "performer" => [
                            [
                                "reference" => "Practitioner/N10000005",
                                "display" => "Fatma",
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "text" =>
                                    "Periksa Kolesterol Darah untuk Pelayanan Rawat Inap Pasien a.n Diana Smith",
                            ],
                        ],
                        "reasonReference" => [
                            [
                                "Reference" =>
                                    "urn:uuid:e60551d9-7887-4ed4-bc4f-1a53e706d30f",
                            ],
                        ],
                        "supportingInfo" => [
                            [
                                "reference" =>
                                    "urn:uuid:2a9d3ebd-2ff7-4f38-96c6-2f76e989720f",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ],
                [
                    "fullUrl" => "urn:uuid:720dc040-578d-45d3-8868-85c0fdfe6115",
                    "resource" => [
                        "resourceType" => "Specimen",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/specimen/10085103",
                                "value" => "23456789",
                                "assigner" => ["reference" => "Organization/10085103"],
                            ],
                        ],
                        "status" => "available",
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "119297000",
                                    "display" => "Blood specimen",
                                ],
                            ],
                        ],
                        "condition" => [["text" => "Kondisi Spesimen Baik"]],
                        "collection" => [
                            "method" => [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "82078001",
                                        "display" =>
                                            "Collection of blood specimen for laboratory",
                                    ],
                                ],
                            ],
                            "collectedDateTime" => "2023-03-26T15:00:00+00:00",
                            "quantity" => ["value" => 6, "unit" => "mL"],
                            "collector" => [
                                "reference" => "Practitioner/N10000001",
                                "display" => "Dokter Bronsig",
                            ],
                            "fastingStatusCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0916",
                                        "code" => "NF",
                                        "display" =>
                                            "The patient indicated they did not fast prior to the procedure.",
                                    ],
                                ],
                            ],
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "request" => [
                            [
                                "reference" =>
                                    "urn:uuid:3829fc49-d2bb-4743-acbf-4681895c39c0",
                            ],
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedTime",
                                "valueDateTime" => "2023-03-26T15:15:00+00:00",
                            ],
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedPerson",
                                "valueContactDetail" => [
                                    "name" => "Burhan",
                                    "telecom" => [
                                        ["system" => "phone", "value" => "021-5375162"],
                                    ],
                                ],
                            ],
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/ReceivedPerson",
                                "valueReference" => [
                                    "reference" => "Practitioner/10006926841",
                                    "display" => "Dr. John Doe",
                                ],
                            ],
                        ],
                        "receivedTime" => "2023-03-26T15:25:00+00:00",
                        "processing" => [
                            ["timeDateTime" => "2023-03-27T16:30:00+00:00"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Specimen"],
                ],
                [
                    "fullUrl" => "urn:uuid:37139e6b-f6bb-42bc-b28e-cc36aac186f7",
                    "resource" => [
                        "resourceType" => "Observation",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/observation/10085103",
                                "value" => "O111111",
                            ],
                        ],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "laboratory",
                                        "display" => "Laboratory",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "2093-3",
                                    "display" =>
                                        "Cholesterol [Mass/volume] in Serum or Plasma",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "effectiveDateTime" => "2023-03-29T22:30:10+00:00",
                        "issued" => "2023-03-29T22:30:10+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/N10000001"],
                            ["reference" => "Organization/10085103"],
                        ],
                        "specimen" => [
                            "reference" =>
                                "urn:uuid:720dc004-578d-45d3-8868-85c0fcfe6115",
                        ],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:196e3c3a-6673-4ae0-acd9-1f03a95d7a32",
                            ],
                        ],
                        "valueQuantity" => [
                            "value" => 240,
                            "unit" => "mg/dL",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "mg/dL",
                        ],
                        "interpretation" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                        "code" => "H",
                                        "display" => "High",
                                    ],
                                ],
                            ],
                        ],
                        "referenceRange" => [
                            [
                                "high" => [
                                    "value" => 200,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "text" => "Normal",
                            ],
                            [
                                "low" => [
                                    "value" => 201,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "high" => [
                                    "value" => 239,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "text" => "Borderline high",
                            ],
                            [
                                "low" => [
                                    "value" => 240,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "text" => "High",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],
                [
                    "fullUrl" => "urn:uuid:8169f582-5f2e-4fa2-b594-9c59486ba9e1",
                    "resource" => [
                        "resourceType" => "DiagnosticReport",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/diagnostic/10085103/lab",
                                "use" => "official",
                                "value" => "52343421-B",
                            ],
                        ],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0074",
                                        "code" => "CH",
                                        "display" => "Chemistry",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "2093-3",
                                    "display" =>
                                        "Cholesterol [Mass/volume] in Serum or Plasma",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "effectiveDateTime" => "2023-03-29T22:30:10+00:00",
                        "issued" => "2023-03-30T03:30:00+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/N10000001"],
                            ["reference" => "Organization/10085103"],
                        ],
                        "result" => [
                            [
                                "reference" =>
                                    "urn:uuid:86825f8b-b695-42c3-a0bd-5ec43989e97e",
                            ],
                        ],
                        "specimen" => [
                            [
                                "reference" =>
                                    "urn:uuid:a6244a41-342d-4023-8db3-414875697cd8",
                            ],
                        ],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:6220df5a-611a-4a0e-8545-89c3bd65db06",
                            ],
                        ],
                        "conclusionCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                        "code" => "H",
                                        "display" => "High",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "DiagnosticReport"],
                ],
                [
                    "fullUrl" => "urn:uuid:1963ce3a-6673-4ae0-acd9-1f03a95d7a32",
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/servicerequest/10085103",
                                "value" => "00001A",
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "priority" => "routine",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "108252007",
                                        "display" => "Laboratory procedure",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "2161-8",
                                    "display" => "Creatinine [Mass/volume] in Urine",
                                ],
                            ],
                            "text" => "Kreatinin dalam Urin",
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                            "display" =>
                                "Permintaan Kreatinin dalam Urin Sabtu, 28 Maret 2023 pukul 09:30 WIB",
                        ],
                        "occurrenceDateTime" => "2023-03-30T16:30:00+00:00",
                        "authoredOn" => "2023-03-29T14:00:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/N10000001",
                            "display" => "Dokter Bronsig",
                        ],
                        "performer" => [
                            [
                                "reference" => "Practitioner/N10000005",
                                "display" => "Fatma",
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "text" =>
                                    "Periksa Kreatinin untuk Pelayanan Rawat Inap Pasien a.n Diana Smith",
                            ],
                        ],
                        "reasonReference" => [
                            [
                                "Reference" =>
                                    "urn:uuid:e60551d9-7887-4ed4-bc4f-1a53e706d30f",
                            ],
                        ],
                        "note" => [["text" => "Pasien tidak berpuasa terlebih dahulu"]],
                        "supportingInfo" => [
                            [
                                "reference" =>
                                    "urn:uuid:2a9d3ebd-2ff7-4f38-96c6-2f76e989720f",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ],

                // Sampel / Specimen
                [
                    "fullUrl" => "urn:uuid:720dc004-857d-45d3-8868-85c0fcfe6115",
                    "resource" => [
                        "resourceType" => "Specimen",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/specimen/10085103",
                                "value" => "98765432",
                                "assigner" => ["reference" => "Organization/10085103"],
                            ],
                        ],
                        "status" => "available",
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "122575003",
                                    "display" => "Urine specimen",
                                ],
                            ],
                        ],
                        "condition" => [["text" => "Kondisi Spesimen Baik"]],
                        "collection" => [
                            "method" => [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "82078001",
                                        "display" =>
                                            "Collection of blood specimen for laboratory",
                                    ],
                                ],
                            ],
                            "collectedDateTime" => "2023-03-27T15:00:00+00:00",
                            "quantity" => ["value" => 30, "unit" => "mL"],
                            "collector" => [
                                "reference" => "Practitioner/N10000001",
                                "display" => "Dokter Bronsig",
                            ],
                            "fastingStatusCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0916",
                                        "code" => "NF",
                                        "display" =>
                                            "The patient indicated they did not fast prior to the procedure.",
                                    ],
                                ],
                            ],
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "request" => [
                            [
                                "reference" =>
                                    "urn:uuid:3829fc49-d2bb-4743-acbf-4681895c39c0",
                            ],
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedTime",
                                "valueDateTime" => "2023-03-27T15:15:00+00:00",
                            ],
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedPerson",
                                "valueContactDetail" => [
                                    "name" => "Burhan",
                                    "telecom" => [
                                        ["system" => "phone", "value" => "021-5375162"],
                                    ],
                                ],
                            ],
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/ReceivedPerson",
                                "valueReference" => [
                                    "reference" => "Practitioner/10006926841",
                                    "display" => "Dr. John Doe",
                                ],
                            ],
                        ],
                        "receivedTime" => "2023-03-27T15:25:00+00:00",
                        "processing" => [
                            ["timeDateTime" => "2023-03-28T16:30:00+00:00"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Specimen"],
                ],

                // observation hasil laboratorium
                [
                    "fullUrl" => "urn:uuid:37139eb6-f6bb-42bc-b28e-cc36aac186f7",
                    "resource" => [
                        "resourceType" => "Observation",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/observation/10085103",
                                "value" => "O11111A",
                            ],
                        ],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "laboratory",
                                        "display" => "Laboratory",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "2161-8",
                                    "display" => "Creatinine [Mass/volume] in Urine",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "effectiveDateTime" => "2023-03-30T22:30:10+00:00",
                        "issued" => "2023-03-30T22:30:10+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/N10000001"],
                            ["reference" => "Organization/10085103"],
                        ],
                        "specimen" => [
                            "reference" =>
                                "urn:uuid:9bbf1b01-1426-49fa-a48a-11f80e05dfdc",
                        ],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:3cb0a48f-9a20-48d2-b881-9b88e8a75286",
                            ],
                        ],
                        "valueQuantity" => [
                            "value" => 1.5,
                            "unit" => "mg/dL",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "mg/dL",
                        ],
                        "interpretation" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                        "code" => "H",
                                        "display" => "High",
                                    ],
                                ],
                            ],
                        ],
                        "referenceRange" => [
                            [
                                "high" => [
                                    "value" => 0.7,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "text" => "Normal",
                            ],
                            [
                                "low" => [
                                    "value" => 0.8,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "high" => [
                                    "value" => 1.2,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "text" => "Borderline high",
                            ],
                            [
                                "low" => [
                                    "value" => 1.5,
                                    "unit" => "mg/dL",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg/dL",
                                ],
                                "text" => "High",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],

                // DIAGNOSTIC REPORT
                [
                    "fullUrl" => "urn:uuid:816f9528-5f2e-4fa2-b594-9c59486ba9e1",
                    "resource" => [
                        "resourceType" => "DiagnosticReport",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/diagnostic/10085103/lab",
                                "use" => "official",
                                "value" => "52343421-C",
                            ],
                        ],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0074",
                                        "code" => "CH",
                                        "display" => "Chemistry",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "2161-8",
                                    "display" => "Creatinine [Mass/volume] in Urine",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "effectiveDateTime" => "2023-03-30T22:30:10+00:00",
                        "issued" => "2023-03-29T03:30:00+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/N10000001"],
                            ["reference" => "Organization/10085103"],
                        ],
                        "result" => [
                            [
                                "reference" =>
                                    "urn:uuid:89661100-d823-45f5-8a42-1669a39c795b",
                            ],
                        ],
                        "specimen" => [
                            [
                                "reference" =>
                                    "urn:uuid:9bbf1b01-1426-49fa-a48a-11f80e05dfdc",
                            ],
                        ],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:3cb0a48f-9a20-48d2-b881-9b88e8a75286",
                            ],
                        ],
                        "conclusionCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                        "code" => "H",
                                        "display" => "High",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "DiagnosticReport"],
                ],



                // Medication
                [
                    "fullUrl" => "urn:uuid:aeb3e0d2-aa5d-4b40-8ca1-4cdb50991ee9",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/10085103",
                                "use" => "official",
                                "value" => "1234567-A",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93017701",
                                    "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS066",
                                    "display" => "Tablet",
                                ],
                            ],
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],
                

                // MEDICATIONREQUEST
                [
                    "fullUrl" => "urn:uuid:b95feccf-a74b-4dc4-8077-26127c171ff9",
                    "resource" => [
                        "resourceType" => "MedicationRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/10085103",
                                "use" => "official",
                                "value" => "123456788",
                            ],
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/10085103",
                                "use" => "official",
                                "value" => "123456788-1",
                            ],
                        ],
                        "status" => "completed",
                        "intent" => "order",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                        "code" => "inpatient",
                                        "display" => "Inpatient",
                                    ],
                                ],
                            ],
                        ],
                        "priority" => "routine",
                        "medicationReference" => [
                            "reference" =>
                                "urn:uuid:ce897d50-3829-44da-bb4d-7a24032510a6",
                            "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "authoredOn" => "2023-03-27T14:00:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/N10000001",
                            "display" => "Dokter Bronsig",
                        ],
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "text" => "1 tablet per hari",
                                "additionalInstruction" => [
                                    ["text" => "1 tablet per hari"],
                                ],
                                "patientInstruction" => "1 tablet per hari",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 1,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "dispenseRequest" => [
                            "dispenseInterval" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "validityPeriod" => [
                                "start" => "2022-12-25T14:00:00+00:00",
                                "end" => "2024-05-24T14:00:00+00:00",
                            ],
                            "numberOfRepeatsAllowed" => 0,
                            "quantity" => [
                                "value" => 1,
                                "unit" => "TAB",
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                "code" => "TAB",
                            ],
                            "expectedSupplyDuration" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "performer" => ["reference" => "Organization/10085103"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationRequest"],
                ],

                // Medication
                [
                    "fullUrl" => "urn:uuid:aeb3d0e2-aad5-4b40-8ca1-4cdb50991ee9",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/10085103",
                                "use" => "official",
                                "value" => "1234567-B",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93017701",
                                    "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS066",
                                    "display" => "Tablet",
                                ],
                            ],
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],


                // MedicationRequest
                [
                    "fullUrl" => "urn:uuid:b95fcecf-a7b4-4dc4-8077-26127c171ff9",
                    "resource" => [
                        "resourceType" => "MedicationRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/10085103",
                                "use" => "official",
                                "value" => "123456788",
                            ],
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/10085103",
                                "use" => "official",
                                "value" => "123456788-1",
                            ],
                        ],
                        "status" => "completed",
                        "intent" => "order",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                        "code" => "inpatient",
                                        "display" => "Inpatient",
                                    ],
                                ],
                            ],
                        ],
                        "priority" => "routine",
                        "medicationReference" => [
                            "reference" =>
                                "urn:uuid:0d26babb-f667-4d40-8562-616269ce50ce",
                            "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "authoredOn" => "2023-03-28T14:00:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/N10000001",
                            "display" => "Dokter Bronsig",
                        ],
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "text" => "1 tablet per hari",
                                "additionalInstruction" => [
                                    ["text" => "1 tablet per hari"],
                                ],
                                "patientInstruction" => "1 tablet per hari",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 1,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "dispenseRequest" => [
                            "dispenseInterval" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "validityPeriod" => [
                                "start" => "2022-12-25T14:00:00+00:00",
                                "end" => "2024-05-24T14:00:00+00:00",
                            ],
                            "numberOfRepeatsAllowed" => 0,
                            "quantity" => [
                                "value" => 1,
                                "unit" => "TAB",
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                "code" => "TAB",
                            ],
                            "expectedSupplyDuration" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "performer" => ["reference" => "Organization/10085103"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationRequest"],
                ],

                // medication
                [
                    "fullUrl" => "urn:uuid:aeb30de2-aa5d-4b40-8ca1-4cdb50991ee9",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/10085103",
                                "use" => "official",
                                "value" => "1234567-C",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93017701",
                                    "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS066",
                                    "display" => "Tablet",
                                ],
                            ],
                        ],
                        "batch" => [
                            "lotNumber" => "1625042A",
                            "expirationDate" => "2025-07-29T00:00:00+00:00",
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],

                // medication request
                [
                    "fullUrl" => "urn:uuid:b95fcefc-a74b-4dc4-8077-26127c171ff9",
                    "resource" => [
                        "resourceType" => "MedicationRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/10085103",
                                "use" => "official",
                                "value" => "123456788",
                            ],
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/10085103",
                                "use" => "official",
                                "value" => "123456788-1",
                            ],
                        ],
                        "status" => "completed",
                        "intent" => "order",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                        "code" => "inpatient",
                                        "display" => "Inpatient",
                                    ],
                                ],
                            ],
                        ],
                        "priority" => "routine",
                        "medicationReference" => [
                            "reference" =>
                                "urn:uuid:33dfa1c6-73d3-4e2c-9761-8a9860211a10",
                            "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "authoredOn" => "2023-03-29T14:00:00+00:00",
                        "requester" => [
                            "reference" => "Practitioner/N10000001",
                            "display" => "Dokter Bronsig",
                        ],
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "text" => "1 tablet per hari",
                                "additionalInstruction" => [
                                    ["text" => "1 tablet per hari"],
                                ],
                                "patientInstruction" => "1 tablet per hari",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 1,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        "dispenseRequest" => [
                            "dispenseInterval" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "validityPeriod" => [
                                "start" => "2022-12-25T14:00:00+00:00",
                                "end" => "2024-05-24T14:00:00+00:00",
                            ],
                            "numberOfRepeatsAllowed" => 0,
                            "quantity" => [
                                "value" => 1,
                                "unit" => "TAB",
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                "code" => "TAB",
                            ],
                            "expectedSupplyDuration" => [
                                "value" => 1,
                                "unit" => "days",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "d",
                            ],
                            "performer" => ["reference" => "Organization/10085103"],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationRequest"],
                ],

                // QuestionnaireResponse
                [
                    "fullUrl" => "urn:uuid:68ddb082-2775-4545-a0f3-705b463fcd97",
                    "resource" => [
                        "resourceType" => "QuestionnaireResponse",
                        "questionnaire" =>
                            "https://fhir.kemkes.go.id/Questionnaire/Q0007",
                        "status" => "completed",
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "authored" => "2023-03-28T10:00:00+07:00",
                        "author" => ["reference" => "Practitioner/N10000001"],
                        "source" => ["reference" => "Patient/100000030015"],
                        "item" => [
                            [
                                "linkId" => "1",
                                "text" => "Persyaratan Administrasi",
                                "item" => [
                                    [
                                        "linkId" => "1.1",
                                        "text" =>
                                            "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "1.2",
                                        "text" =>
                                            "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "1.3",
                                        "text" => "Apakah tanggal resep sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "1.4",
                                        "text" =>
                                            "Apakah ruangan/unit asal resep sudah sesuai?",
                                        "answer" => [
                                            [
                                                "valueCoding" => [
                                                    "system" =>
                                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                    "code" => "OV000052",
                                                    "display" => "Sesuai",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "2",
                                        "text" => "Persyaratan Farmasetik",
                                        "item" => [
                                            [
                                                "linkId" => "2.1",
                                                "text" =>
                                                    "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "2.2",
                                                "text" =>
                                                    "Apakah dosis dan jumlah obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "2.3",
                                                "text" =>
                                                    "Apakah stabilitas obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "2.4",
                                                "text" =>
                                                    "Apakah aturan dan cara penggunaan obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "linkId" => "3",
                                        "text" => "Persyaratan Klinis",
                                        "item" => [
                                            [
                                                "linkId" => "3.1",
                                                "text" =>
                                                    "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
                                                "answer" => [
                                                    [
                                                        "valueCoding" => [
                                                            "system" =>
                                                                "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                            "code" => "OV000052",
                                                            "display" => "Sesuai",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                "linkId" => "3.2",
                                                "text" =>
                                                    "Apakah terdapat duplikasi pengobatan?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                            [
                                                "linkId" => "3.3",
                                                "text" =>
                                                    "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                            [
                                                "linkId" => "3.4",
                                                "text" =>
                                                    "Apakah terdapat kontraindikasi pengobatan?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                            [
                                                "linkId" => "3.5",
                                                "text" =>
                                                    "Apakah terdapat dampak interaksi obat?",
                                                "answer" => [["valueBoolean" => false]],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "QuestionnaireResponse"],
                ],

                // medication
                [
                    "fullUrl" => "urn:uuid:1e1102cd-a791-42e5-86fd-fbb061ddaac8",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/10085103",
                                "use" => "official",
                                "value" => "1234567-D",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93017701",
                                    "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS066",
                                    "display" => "Tablet",
                                ],
                            ],
                        ],
                        "batch" => [
                            "lotNumber" => "1625042A",
                            "expirationDate" => "2025-07-29T00:00:00+00:00",
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],

                // medication dispense
                [
                    "fullUrl" => "urn:uuid:703dabce-102a-4fb2-954c-aeb33b6a56be",
                    "resource" => [
                        "resourceType" => "MedicationDispense",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/10085103",
                                "use" => "official",
                                "value" => "123456789",
                            ],
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/10085103",
                                "use" => "official",
                                "value" => "123456788-2",
                            ],
                        ],
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                    "code" => "inpatient",
                                    "display" => "Inpatient",
                                ],
                            ],
                        ],
                        "medicationReference" => [
                            "reference" =>
                                "urn:uuid:76d67b9f-2831-4000-b974-4e32bb1b9a04",
                            "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "context" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/N10000001",
                                    "display" => "Dokter Bronsig",
                                ],
                            ],
                        ],
                        "location" => [
                            "reference" =>
                                "Location/b29038d4-9ef0-4eb3-a2e9-3c02df668b07",
                            "display" =>
                                "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                        ],
                        "authorizingPrescription" => [
                            [
                                "reference" =>
                                    "urn:uuid:b5d747e8-4b4f-43cc-be06-6d207cacc332",
                            ],
                        ],
                        "quantity" => [
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                            "code" => "TAB",
                            "value" => 1,
                        ],
                        "daysSupply" => [
                            "value" => 1,
                            "unit" => "Day",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "whenPrepared" => "2023-03-27T14:00:00+00:00",
                        "whenHandedOver" => "2023-03-27T14:30:00+00:00",
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "text" => "1 tablet per hari",
                                "additionalInstruction" => [
                                    ["text" => "1 tablet per hari"],
                                ],
                                "patientInstruction" => "1 tablet per hari",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 1,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationDispense"],
                ],

                // Medication
                [
                    "fullUrl" => "urn:uuid:11e120dc-a791-42e5-86fd-fbb061ddaca8",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/10085103",
                                "use" => "official",
                                "value" => "1234567-E",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93017701",
                                    "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS066",
                                    "display" => "Tablet",
                                ],
                            ],
                        ],
                        "batch" => [
                            "lotNumber" => "1625042A",
                            "expirationDate" => "2025-07-29T00:00:00+00:00",
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],

                // MedicationDispense
                [
                    "fullUrl" => "urn:uuid:703dabec-10a2-2f4b-954c-aeb33b6a56be",
                    "resource" => [
                        "resourceType" => "MedicationDispense",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/10085103",
                                "use" => "official",
                                "value" => "123456789",
                            ],
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/10085103",
                                "use" => "official",
                                "value" => "123456788-2",
                            ],
                        ],
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                    "code" => "inpatient",
                                    "display" => "Inpatient",
                                ],
                            ],
                        ],
                        "medicationReference" => [
                            "reference" =>
                                "urn:uuid:db07896f-ede8-48fa-9bf0-e99941a6a375",
                            "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "context" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/N10000001",
                                    "display" => "Dokter Bronsig",
                                ],
                            ],
                        ],
                        "location" => [
                            "reference" =>
                                "Location/b29038d4-9ef0-4eb3-a2e9-3c02df668b07",
                            "display" =>
                                "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                        ],
                        "authorizingPrescription" => [
                            [
                                "reference" =>
                                    "urn:uuid:78c5d8d1-e3bf-4ff1-bf3c-6aa564336ee7",
                            ],
                        ],
                        "quantity" => [
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                            "code" => "TAB",
                            "value" => 1,
                        ],
                        "daysSupply" => [
                            "value" => 1,
                            "unit" => "Day",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "whenPrepared" => "2023-03-29T14:00:00+00:00",
                        "whenHandedOver" => "2023-03-29T14:30:00+00:00",
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "text" => "1 tablet per hari",
                                "additionalInstruction" => [
                                    ["text" => "1 tablet per hari"],
                                ],
                                "patientInstruction" => "1 tablet per hari",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 1,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationDispense"],
                ],


                // medication
                [
                    "fullUrl" => "urn:uuid:1e112d0c-a917-42e5-86fd-fbb061ddaca8",
                    "resource" => [
                        "resourceType" => "Medication",
                        "meta" => [
                            "profile" => [
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                            ],
                        ],
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/medication/10085103",
                                "use" => "official",
                                "value" => "1234567-F",
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "93017701",
                                    "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                                ],
                            ],
                        ],
                        "status" => "active",
                        "manufacturer" => ["reference" => "Organization/900001"],
                        "form" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                    "code" => "BS066",
                                    "display" => "Tablet",
                                ],
                            ],
                        ],
                        "batch" => [
                            "lotNumber" => "1625042A",
                            "expirationDate" => "2025-07-29T00:00:00+00:00",
                        ],
                        "extension" => [
                            [
                                "url" =>
                                    "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                            "code" => "NC",
                                            "display" => "Non-compound",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Medication"],
                ],


                // medication dispense
                [
                    "fullUrl" => "urn:uuid:703dabce-120a-4f2b-954c-aeb33b6a56be",
                    "resource" => [
                        "resourceType" => "MedicationDispense",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription/10085103",
                                "use" => "official",
                                "value" => "123456789",
                            ],
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/prescription-item/10085103",
                                "use" => "official",
                                "value" => "123456788-2",
                            ],
                        ],
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                    "code" => "inpatient",
                                    "display" => "Inpatient",
                                ],
                            ],
                        ],
                        "medicationReference" => [
                            "reference" =>
                                "urn:uuid:d25201d0-d6de-483a-98f3-6dc43fc3efc4",
                            "display" => "VITAMIN B6 25 mg TABLET (Umum)",
                        ],
                        "subject" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "context" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/N10000001",
                                    "display" => "Dokter Bronsig",
                                ],
                            ],
                        ],
                        "location" => [
                            "reference" =>
                                "Location/b29038d4-9ef0-4eb3-a2e9-3c02df668b07",
                            "display" =>
                                "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                        ],
                        "authorizingPrescription" => [
                            [
                                "reference" =>
                                    "urn:uuid:c6a79ce0-b2da-4b00-8f41-60187b839db3",
                            ],
                        ],
                        "quantity" => [
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                            "code" => "TAB",
                            "value" => 1,
                        ],
                        "daysSupply" => [
                            "value" => 1,
                            "unit" => "Day",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "whenPrepared" => "2023-03-30T14:00:00+00:00",
                        "whenHandedOver" => "2023-03-30T14:30:00+00:00",
                        "dosageInstruction" => [
                            [
                                "sequence" => 1,
                                "text" => "1 tablet per hari",
                                "additionalInstruction" => [
                                    ["text" => "1 tablet per hari"],
                                ],
                                "patientInstruction" => "1 tablet per hari",
                                "timing" => [
                                    "repeat" => [
                                        "frequency" => 1,
                                        "period" => 1,
                                        "periodUnit" => "d",
                                    ],
                                ],
                                "route" => [
                                    "coding" => [
                                        [
                                            "system" => "http://www.whocc.no/atc",
                                            "code" => "O",
                                            "display" => "Oral",
                                        ],
                                    ],
                                ],
                                "doseAndRate" => [
                                    [
                                        "type" => [
                                            "coding" => [
                                                [
                                                    "system" =>
                                                        "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                    "code" => "ordered",
                                                    "display" => "Ordered",
                                                ],
                                            ],
                                        ],
                                        "doseQuantity" => [
                                            "value" => 1,
                                            "unit" => "TAB",
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                            "code" => "TAB",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "MedicationDispense"],
                ],

                
                [
                    "fullUrl" => "urn:uuid:39ad4a1c-dc1b-4a71-9c59-778b6c1503d3",
                    "resource" => [
                        "resourceType" => "Observation",
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "survey",
                                        "display" => "Survey",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "82810-3",
                                    "display" => "Pregnancy status",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "performer" => [["reference" => "Practitioner/N10000001"]],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                            "display" =>
                                "Kunjungan Diana Smith di hari Kamis, 26 Maret 2023",
                        ],
                        "effectiveDateTime" => "2023-03-26T08:00:00+00:00",
                        "issued" => "2023-03-26T08:00:00+00:00",
                        "valueCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "60001007",
                                    "display" => "Not pregnant",
                                ],
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],
                [
                    "fullUrl" => "urn:uuid:3feb620d-8688-4349-b5bc-ff25277e0021",
                    "resource" => [
                        "resourceType" => "AllergyIntolerance",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/allergy/10085103",
                                "use" => "official",
                                "value" => "123456789",
                            ],
                        ],
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                    "code" => "active",
                                    "display" => "Active",
                                ],
                            ],
                        ],
                        "verificationStatus" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                    "code" => "confirmed",
                                    "display" => "Confirmed",
                                ],
                            ],
                        ],
                        "category" => ["medication"],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/kfa",
                                    "code" => "91000918",
                                    "display" => "IODIXANOL",
                                ],
                            ],
                            "text" => "Alergi Bahan Kontras Iodixanol",
                        ],
                        "patient" => [
                            "reference" => "Patient/100000030015",
                            "display" => "Diana Smith",
                        ],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                            "display" =>
                                "Kunjungan Diana Smith di hari Kamis, 25 Maret 2023",
                        ],
                        "recordedDate" => "2023-03-25T08:00:00+00:00",
                        "recorder" => ["reference" => "Practitioner/N10000001"],
                    ],
                    "request" => ["method" => "POST", "url" => "AllergyIntolerance"],
                ],
                [
                    "fullUrl" => "urn:uuid:196e3c3a-6763-4ae0-adc9-1f03a95d7a32",
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/servicerequest/10085103",
                                "value" => "00001B",
                            ],
                            [
                                "use" => "usual",
                                "type" => [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v2-0203",
                                            "code" => "ACSN",
                                        ],
                                    ],
                                ],
                                "system" => "http://sys-ids.kemkes.go.id/acsn/10085103",
                                "value" => "21120054",
                            ],
                        ],
                        "status" => "active",
                        "intent" => "original-order",
                        "priority" => "routine",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "363679005",
                                        "display" => "Imaging",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "46322-4",
                                    "display" => "CT Kidney W contrast IV",
                                ],
                            ],
                            "text" => "Pemeriksaan CT Scan Ginjal",
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "occurrenceDateTime" => "2023-03-28T16:30:27+00:00",
                        "authoredOn" => "2023-03-28T19:30:27+00:00",
                        "requester" => [
                            "reference" => "Practitioner/N10000001",
                            "display" => "Dokter Bronsig",
                        ],
                        "performer" => [
                            [
                                "reference" => "Practitioner/N10000005",
                                "display" => "Fatma",
                            ],
                        ],
                        "bodySite" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "64033007",
                                        "display" => "Kidney structure",
                                    ],
                                ],
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "text" =>
                                    "Pemeriksaan CT Scan Ginjal untuk Pelayanan Rawat Inap Pasien a.n Diana Smith",
                            ],
                        ],
                        "reasonReference" => [
                            [
                                "Reference" =>
                                    "urn:uuid:e60551d9-7887-4ed4-bc4f-1a53e706d30f",
                            ],
                        ],
                        "note" => [["text" => "Pemeriksaan CT Scan Ginjal"]],
                        "supportingInfo" => [
                            [
                                "reference" =>
                                    "urn:uuid:0f75c423-7fd4-41ea-8922-a2424838cef6",
                            ],
                            [
                                "reference" =>
                                    "urn:uuid:a717ee01-8426-4bc7-a1d4-ae2db0d2a7c9",
                            ],
                        ],
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ],
                [
                    "fullUrl" => "urn:uuid:37193eb6-f6bb-42cb-b28e-cc36aac186f7",
                    "resource" => [
                        "resourceType" => "Observation",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/observation/10085103",
                                "value" => "O111111B",
                            ],
                        ],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/observation-category",
                                        "code" => "imaging",
                                        "display" => "Imaging",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "46322-4",
                                    "display" => "CT Kidney W contrast IV",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "effectiveDateTime" => "2023-03-28T23:30:10+00:00",
                        "issued" => "2023-03-28T23:30:10+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/N10000001"],
                            ["reference" => "Organization/10085103"],
                        ],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:09e32d32-9c72-442d-a557-a9e3887d63a0",
                            ],
                        ],
                        "bodySite" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "64033007",
                                    "display" => "Kidney structure",
                                ],
                            ],
                        ],
                        "derivedFrom" => [
                            [
                                "reference" =>
                                    "urn:uuid:c4f3bfe3-91cd-40c4-b986-000c2150f051",
                            ],
                        ],
                        "valueString" => "Ditemukan kelainan dalam CT Kidney",
                    ],
                    "request" => ["method" => "POST", "url" => "Observation"],
                ],

                [
                    "fullUrl" => "urn:uuid:816f9852-5f2e-4fa2-b594-9c59486ba9e1",
                    "resource" => [
                        "resourceType" => "DiagnosticReport",
                        "identifier" => [
                            [
                                "system" =>
                                    "http://sys-ids.kemkes.go.id/diagnostic/10085103/rad",
                                "use" => "official",
                                "value" => "52343421-A",
                            ],
                        ],
                        "status" => "final",
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0074",
                                        "code" => "RAD",
                                        "display" => "Radiology",
                                    ],
                                ],
                            ],
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "87847-0",
                                    "display" =>
                                        "CT Chest WO and CT angiogram Coronary arteries W contrast IV",
                                ],
                            ],
                        ],
                        "subject" => ["reference" => "Patient/100000030015"],
                        "encounter" => [
                            "reference" =>
                                "urn:uuid:6db27d20-20a6-4a29-8925-83025b1d8c35",
                        ],
                        "effectiveDateTime" => "2023-03-27T01:00:00+00:00",
                        "issued" => "2023-03-28T15:00:00+00:00",
                        "performer" => [
                            ["reference" => "Practitioner/N10000001"],
                            ["reference" => "Organization/10085103"],
                        ],
                        "imagingStudy" => [
                            [
                                "reference" =>
                                    "urn:uuid:c4f3bfe3-91cd-40c4-b986-000c2150f051",
                            ],
                        ],
                        "result" => [
                            [
                                "reference" =>
                                    "urn:uuid:d825bb67-1357-425c-b1a9-0ed6e89a4339",
                            ],
                        ],
                        "basedOn" => [
                            [
                                "reference" =>
                                    "urn:uuid:09e32d32-9c72-442d-a557-a9e3887d63a0",
                            ],
                        ],
                        "conclusion" => "Ditemukan Sumbatan pada bagian Saluran Kemih",
                    ],
                    "request" => ["method" => "POST", "url" => "DiagnosticReport"],
                ],
            ],
        ];
        
    }
}
