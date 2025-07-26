<?php

namespace App\Http\Controllers;

use App\Models\LaboratLuar;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class PrintController extends Controller
{

    public function index()
    {
        $page = request('data');
        $params = request('q');
        if ($page === 'permintaan-laborat-luar') {
            return $this->print_permintaan_luar($params, 'pengantar');
        }
        if ($page === 'hasil-permintaan-laborat-luar') {
            return $this->print_permintaan_luar($params, 'hasil');
        }
        if ($page === 'hasil-permintaan-laborat-dalam') {
            return $this->print_permintaan_dalam($params, 'hasil');
        }
    }

    public function print_header()
    {
        $header = (object) array(
            'title' => 'UOBK RSUD dr. MOHAMAD SALEH',
            'sub' => 'Jl. Mayjend Panjaitan No. 65 Probolinggo Jawa Timur',
            'sub2' => 'Telp. (0335) 433478,433119,421118 Fax. (0335) 432702',
        );
        return $header;
    }

    public function print_permintaan_luar($q, $jns)
    {
        $header = $this->print_header();
        $details = LaboratLuar::query()
            ->selectRaw('
            nama, kelamin, alamat,tgl_lahir,
            nota,tgl,pengirim,hasil,hl,kd_lab,jml,tarif_sarana,tarif_pelayanan,
            sampel_diambil,jam_sampel_diambil,sampel_selesai,jam_sampel_selesai,ket,
            (tarif_sarana + tarif_pelayanan) as biaya, ((tarif_sarana + tarif_pelayanan)* jml) as subtotal, metode, tat')
            ->where('nota', $q)
            ->with(['perusahaan', 'pemeriksaan_laborat', 'catatan'])
            ->get();

        $data = array(
            'jenis' => $jns,
            'header' => $header,
            'details' => $details
        );

        return view('print.permintaan_laborat_luar', $data);
    }
    public function print_permintaan_dalam($q, $jns)
    {
        $header = $this->print_header();
        $details = TransaksiLaborat::query()
            // ->selectRaw('rs1,rs2,rs3 as tanggal,rs20,rs8,rs23,rs18,rs21,rs29,rs4, (rs6 + rs7) as biaya, rs5 as jumlah,((rs6 + rs7)* rs5) as subtotal,rs27 as flag, metode,tat')
            ->select(
                'rs51.rs1',
                'rs51.rs2',
                'rs51.rs3 as tanggal',
                'rs51.rs20',
                'rs51.rs8',
                'rs51.rs23',
                'rs51.rs18',
                'rs51.rs21',
                'rs51.rs29',
                'rs51.rs4',
                'rs51.rs5 as jumlah',
                'rs51.rs27 as flag',
                'rs51.metode',
                'rs51.tat',
                'rs49.tampilanurut as urut',
                'rs49.rs21 as group',
                DB::raw('rs51.rs6 + rs51.rs7 as biaya'),
                DB::raw('(rs51.rs6 + rs51.rs7)* rs51.rs5  as subtotal'),
            )
            ->leftjoin('rs49', 'rs49.rs1', '=', 'rs51.rs4')
            ->where('rs51.rs2', $q)
            ->where('rs49.hidden', '!=', '1')
            ->with([
                'kunjungan_poli',
                'kunjungan_rawat_inap',
                'sb_kunjungan_poli',
                'sb_kunjungan_rawat_inap',
                'kunjungan_rawat_inap.ruangan',
                'poli',
                'dokter',
                'pasien_kunjungan_poli',
                'pasien_kunjungan_rawat_inap',
                'pemeriksaan_laborat'
            ])
            ->get()->sortBy('urut')->values();

        $data = array(
            'jenis' => $jns,
            'header' => $header,
            'details' => $details
        );

        return view('print.permintaan_laborat_dalam', $data);
    }
}
