<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Anamnesis\AnamnesisKebidanan;
use App\Models\Simrs\Anamnesis\HistoryKehamilan;
use App\Models\Simrs\Anamnesis\HistoryPerkawinan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisKebidananController extends Controller
{
    public function simpanHistoryPerkawiananPasien(Request $request)
    {
        $simpan = HistoryPerkawinan::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'suami_ke' => $request->suamike,
                'lamapernikahan' => $request->lamapernikahan
            ]
        );
        return new JsonResponse(['message' => 'Data Sudah Tersimpan...!!!','result' => $simpan], 200);
    }

    public function hapusHistoryPerkawiananPasien(Request $request)
    {

        $simpan = HistoryPerkawinan::where('id', $request->id);
        $hapus = $simpan->delete();
        return new JsonResponse(['message' => 'Data Sudah Terhapus...!!!','result' => $hapus], 200);
    }

    public function simpanHistoryKehamilan(Request $request)
    {
        $simpan = HistoryKehamilan::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tanggal_partus' => $request->tanggal,
                'tempat' => $request->tempat,
                'umurkehamilan' => $request->umurkehamilan,
                'jenispersalinan' => $request->jenispersalinan,
                'penolong' => $request->penolong,
                'penyulit' => $request->penyulit,
                'jeniskelamin' => $request->jk,
                'beratbadan' => $request->bb,
                'pb' => $request->pb,
                'nifas' => $request->nifas,
                //'user' => $request->norm,
            ]
        );
        return new JsonResponse(['message' => 'Data Sudah Tersimpan...!!!','result' => $simpan], 200);
    }

    public function hapusHistoryKehamilan(Request $request)
    {

        $simpan = HistoryKehamilan::where('id', $request->id);
        $hapus = $simpan->delete();
        return new JsonResponse(['message' => 'Data Sudah Terhapus...!!!','result' => $hapus], 200);
    }

    public function simpanananamesiskebidanan(Request $request)
    {
        $simpan = AnamnesisKebidanan::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'keluhanutama' => $request->keluhanutama,
                'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang,
                'riwayatpenyakit' => $request->riwayatpenyakit,
                'riwayatpengobatan' => $request->riwayatpengobatan,
                'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga,
                'riwayatpekerjaan' => $request->riwayatpekerjaan,
                'riwayatalergi' => $request->riwayatalergi,
                'keteranganalergi' => $request->keteranganalergi,
                'optionskriniggizi' => $request->optionskriniggizi,
                'skreeninggizi' => $request->skreeninggizi,
                'asupanmakan' => $request->asupanmakan,
                'kondisikhusus' => $request->kondisikhusus,
                'skorgizi' => $request->skorgizi,
                'asupanmakanberkurang' => $request->asupanmakanberkurang,
                'metabolisme' => $request->metabolisme,
                'penambahanbb' => $request->penambahanbb,
                'nilaihbberkurang' => $request->nilaihbberkurang,
                'skorgizix' => $request->skorgizix,
                'metodenyeri' => $request->metodenyeri,
                'skornyeri' => $request->skornyeri,
                'keteranganscorenyeri' => $request->keteranganscorenyeri,
                'ekspresiwajah' => $request->ekspresiwajah,
                'gerakantangan' => $request->gerakantangan,
                'kepatuhanventilasimekanik' => $request->kepatuhanventilasimekanik,
                'scroebps' => $request->scroebps,
                'ketscorebps' => $request->ketscorebps,
                'ekspresiwajahnips' => $request->ekspresiwajahnips,
                'menangis' => $request->menangis,
                'polanafas' => $request->polanafas,
                'lengan' => $request->lengan,
                'kaki' => $request->kaki,
                'keadaanrangsangan' => $request->keadaanrangsangan,
                'scroenips' => $request->scroenips,
                'ketscorenips' => $request->ketscorenips,
                'lokasinyeri' => $request->lokasinyeri,
                'durasinyeri' => $request->durasinyeri,
                'penyebabnyeri' => $request->penyebabnyeri,
                'frekwensinyeri' => $request->frekwensinyeri,
                'nyerihilang' => $request->nyerihilang,
                'sebutkannyerihilang' => $request->sebutkannyerihilang,
                'kebutuhankomunikasidanedukasi' => $request->kebutuhankomunikasidanedukasi,
                'sebutkankomunaksilainnya' => $request->sebutkankomunaksilainnya,
                'penerjemah' => $request->penerjemah,
                'sebutkanpenerjemah' => $request->sebutkanpenerjemah,
                'bahasaisyarat' => $request->bahasaisyarat,
                'hamabatan' => $request->hamabatan,
                'sebutkanhambatan' => $request->sebutkanhambatan,
                'alatkontrasepsi' => $request->alatkontrasepsi,
                'jeniskontasepsi' => $request->jeniskontasepsi,
                'tahunlamapemakaiankontrasepsi' => $request->tahunlamapemakaiankontrasepsi,
                'bulanlamapemakaiankontrasepsi' => $request->bulanlamapemakaiankontrasepsi,
                'minggulamapemakaiankontrasepsi' => $request->minggulamapemakaiankontrasepsi,
                'harilamapemakaiankontrasepsi' => $request->harilamapemakaiankontrasepsi,
                'keluhankontrasepsi' => $request->keluhankontrasepsi,
                'statuspernikahan' => $request->statuspernikahan,
                'jumlahpernikahan' => $request->jumlahpernikahan,
                'umurpertamanikah' => $request->umurpertamanikah,
                'menarcheumur'  => $request->menarcheumur,
                'siklus' => $request->siklus,
                'keteraturan' => $request->keteraturan,
                'lamahaririwayatmens' => $request->lamahaririwayatmens,
                'keluhanhaid' => $request->keluhanhaid,
                'sebutkankeluhanhaid' => $request->sebutkankeluhanhaid,
                'riwayatginekologi' => $request->riwayatginekologi,
                'ginekologis' => $request->ginekologis,
                'sebutkanginekologis' => $request->sebutkanginekologis,
                'haid' => $request->haid,
                'gravida' => $request->gravida,
                'partus' => $request->partus,
                'abortus' => $request->abortus,
                'taksiranpartus' => $request->taksiranpartus,
                'asupanantenatal' => $request->asupanantenatal,
                'updateasupanantenatal' => $request->updateasupanantenatal,
                'sebutkanasupanantenatal' => $request->sebutkanasupanantenatal,
                'frekuensi' => $request->frekuensi,
                'imunisasitt' => $request->imunisasitt,
                'sebutkanimunisasitt' => $request->sebutkanimunisasitt,
                'keluhanhamil' => $request->keluhanhamil,
                'sebutkeluhanhamils' => $request->sebutkeluhanhamils,
                'periksaluarginekologi' => $request->periksaluarginekologi,
                'inspekuloginekologi' => $request->inspekuloginekologi,
                'periksadalamginekologi' => $request->periksadalamginekologi,
            ]
        );
        return new JsonResponse(['message' => 'Data Sudah Tersimpan...!!!','result' => $simpan], 200);
    }

    public function hapusanamnesiskebidanan(Request $request)
    {
        $cari = AnamnesisKebidanan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
        // return new JsonResponse($cari, 200);
    }
}
