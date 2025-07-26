<?php

namespace App\Helpers;

use App\Models\Sigarang\BarangRS;
use Illuminate\Support\Facades\DB;

class StokHelper
{
    public static function barangByDepo()
    {

        $raw = BarangRS::get();
        $col = collect($raw);
        $barang = $col->groupBy('kode_depo');
        return $barang;
    }
    public static function hitungTransaksiPemesanan($header)
    {
    }
    public static function hitungTransaksiPenerimaan($header)
    {
        $data = DB::connection('sigarang')
            ->table('penerimaans')
            ->join('detail_penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
            ->join('satuans', 'satuans.kode', '=', 'detail_penerimaans.kode_satuan')
            ->select(
                'penerimaans.nomor',
                'penerimaans.status',
                'penerimaans.no_penerimaan',
                'penerimaans.tanggal',
                'detail_penerimaans.kode_rs',
                'detail_penerimaans.qty',
                'satuans.nama as satuan'
            )
            ->whereBetween('penerimaans.tanggal', [$header->from, $header->to])
            ->where('penerimaans.status', 2)->get();
        return $data;
    }
    public static function hitungTransaksiDistribusiGudang($header)
    {
    }
    public static function hitungTransaksiDistribusiDepo($header)
    {
        $data = DB::connection('sigarang')
            ->table('distribusi_depos')
            ->join('detail_distribusi_depos', 'distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id')
            ->join('satuans', 'satuans.kode', '=', 'detail_distribusi_depos.kode_satuan')
            ->join('gudangs', 'gudangs.kode', '=', 'distribusi_depos.kode_depo')
            ->select(
                'distribusi_depos.no_distribusi',
                'distribusi_depos.kode_depo',
                'distribusi_depos.tanggal',
                'distribusi_depos.status',
                'detail_distribusi_depos.kode_rs',
                'detail_distribusi_depos.no_penerimaan',
                'detail_distribusi_depos.jumlah',
                'satuans.nama as satuan',
                'gudangs.nama as tujuan',
            )
            ->whereBetween('distribusi_depos.tanggal', [$header->thisMonthFrom, $header->thisMonthTo])
            ->where('distribusi_depos.status', 2)->get();

        return $data;
    }
    public static function hitungTransaksiDistribusiLangsung($header)
    {
    }
    public static function hitungTransaksiPermintaanRuangan($header)
    {
        $data = DB::connection('sigarang')
            ->table('permintaanruangans')
            ->join('detail_permintaanruangans', 'permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id')
            ->join('ruangs', 'ruangs.kode', '=', 'permintaanruangans.kode_ruang')
            ->join('gudangs', 'gudangs.kode', '=', 'permintaanruangans.dari')
            ->join('satuans', 'satuans.kode', '=', 'detail_permintaanruangans.kode_satuan')
            ->select(
                'permintaanruangans.dari',
                'permintaanruangans.tanggal',
                'permintaanruangans.status',
                'detail_permintaanruangans.kode_rs',
                'detail_permintaanruangans.jumlah_distribusi as jumlah',
                'ruangs.uraian as ruang',
                'satuans.nama as satuan',
                'gudangs.nama as depo',
            )
            ->whereBetween('permintaanruangans.tanggal', [$header->thisMonthFrom, $header->thisMonthTo])
            ->where('permintaanruangans.status', '>=', 7)
            ->where('permintaanruangans.status', '<=', 8)
            ->get();

        return $data;
    }
    // by kode barang
    public static function hitungTransaksiPemesananByKodeBarang($header)
    {
    }
    public static function hitungTransaksiPenerimaanByKodeBarang($header)
    {
        $data = DB::connection('sigarang')
            ->table('penerimaans')
            ->join('detail_penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
            ->join('satuans', 'satuans.kode', '=', 'detail_penerimaans.kode_satuan')
            ->select(
                'penerimaans.nomor',
                'penerimaans.status',
                'penerimaans.no_penerimaan',
                'penerimaans.tanggal',
                'detail_penerimaans.kode_rs',
                'detail_penerimaans.qty',
                'satuans.nama as satuan'
            )
            ->where('detail_penerimaans.kode_rs', [$header->kode_rs])
            ->whereBetween('penerimaans.tanggal', [$header->from, $header->to])
            ->where('penerimaans.status', 2)->get();
        return $data;
    }
    public static function hitungTransaksiDistribusiGudangByKodeBarang($header)
    {
    }

    public static function hitungTransaksiDistribusiDepoByKodeBarang($header)
    {
        $data = DB::connection('sigarang')
            ->table('distribusi_depos')
            ->join('detail_distribusi_depos', 'distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id')
            ->join('satuans', 'satuans.kode', '=', 'detail_distribusi_depos.kode_satuan')
            ->join('gudangs', 'gudangs.kode', '=', 'distribusi_depos.kode_depo')
            ->select(
                'distribusi_depos.no_distribusi',
                'distribusi_depos.kode_depo',
                'distribusi_depos.tanggal',
                'distribusi_depos.status',
                'detail_distribusi_depos.kode_rs',
                'detail_distribusi_depos.no_penerimaan',
                'detail_distribusi_depos.jumlah',
                'satuans.nama as satuan',
                'gudangs.nama as tujuan',
            )
            ->where('detail_distribusi_depos.kode_rs', [$header->kode_rs])
            ->whereBetween('distribusi_depos.tanggal', [$header->thisMonthFrom, $header->thisMonthTo])
            ->where('distribusi_depos.status', 2)->get();

        return $data;
    }
    public static function hitungTransaksiDistribusiLangsungByKodeBarang($header)
    {
    }
    public static function hitungTransaksiPermintaanRuanganByKodeBarang($header)
    {
        $data = DB::connection('sigarang')
            ->table('permintaanruangans')
            ->join('detail_permintaanruangans', 'permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id')
            ->join('ruangs', 'ruangs.kode', '=', 'permintaanruangans.kode_ruang')
            ->join('gudangs', 'gudangs.kode', '=', 'permintaanruangans.dari')
            ->join('satuans', 'satuans.kode', '=', 'detail_permintaanruangans.kode_satuan')
            ->select(
                'permintaanruangans.dari',
                'permintaanruangans.tanggal',
                'permintaanruangans.status',
                'detail_permintaanruangans.kode_rs',
                'detail_permintaanruangans.jumlah_distribusi as jumlah',
                'ruangs.uraian as ruang',
                'satuans.nama as satuan',
                'gudangs.nama as depo',
            )
            ->where('detail_permintaanruangans.kode_rs', [$header->kode_rs])
            ->whereBetween('permintaanruangans.tanggal', [$header->thisMonthFrom, $header->thisMonthTo])
            ->where('permintaanruangans.status', '>=', 7)
            ->where('permintaanruangans.status', '<=', 8)
            ->get();

        return $data;
    }
}
