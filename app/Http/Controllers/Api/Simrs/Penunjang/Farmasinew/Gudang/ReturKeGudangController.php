<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\ReturGudang;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\ReturGudangDetail;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturKeGudangController extends Controller
{
    // simpan
    public function simpan(Request $request)
    {

        try {
            DB::connection('farmasi')->beginTransaction();
            if ($request->no_retur === null) {
                DB::connection('farmasi')->select('call retgud(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('retgud')->first();
                $nom = $x->retgud;
                $noretur = self::setNomor($nom, 'RETG');
            } else {
                $noretur = $request->no_retur;
            }


            $tdkAdaStok = [];
            $adaStok = [];
            $rinciNya = [];
            $kode = collect($request->details)->map(function ($it) {
                return $it['kd_obat'];
            });
            // stok
            // $stokCek = Stokrel::lockForUpdate()
            //     ->whereIn('kdobat', $kode)
            //     ->where('kdruang', $request->depo)
            //     ->where('jumlah', '>', 0)
            //     ->orderBy('tglexp', 'ASC')
            //     ->get();
            $stok = Stokrel::lockForUpdate()
                ->whereIn('kdobat', $kode)
                ->where('kdruang', $request->depo)
                ->where('jumlah', '>', 0)
                ->orderBy('tglexp', 'ASC')
                ->get();
            $stokNya = collect($stok);
            $stokG = Stokrel::lockForUpdate()
                ->whereIn('kdobat', $kode)
                ->where('kdruang', $request->gudang)
                ->orderBy('tglexp', 'ASC')
                ->get();
            $stokGud = collect($stokG);
            foreach ($request->details as $det) {
                $cekStok = $stokNya->whereIn('kdobat', $det['kd_obat']);
                $jml = $cekStok->sum('jumlah');
                $jml_retur = $det['jumlah_retur'];
                $temp = [
                    'det' => $det,
                    'cekStok' => $cekStok,
                    'jml' => $jml,
                    'jml_retur' => $jml_retur,
                ];
                if ((float)$jml < (float)$jml_retur) {
                    $tdkAdaStok[] = $temp;
                } else {
                    $adaStok[] = $temp;
                }
            }
            if (sizeof($tdkAdaStok) > 0) {
                return new JsonResponse([
                    'message' => 'Stok Tidak mencukupi',
                    'data' => $tdkAdaStok
                ], 410);
            }
            foreach ($request->details as $key) {
                // $cekStok = $stokNya->whereIn('kdobat', $key['kd_obat'])->toArray;
                // return new JsonResponse([
                //     'message' => 'anu',
                //     'data' => $cekStok[0]
                // ], 410);
                $masuk = $key['jumlah_retur'];
                // $index = 0;
                while ($masuk > 0) {
                    $cekStok = $stokNya->where('kdobat', $key['kd_obat'])->where('jumlah', '>', 0)->first();
                    $sisa = $cekStok->jumlah;
                    if ($sisa < $masuk) {
                        $sisax = $masuk - $sisa;
                        $rinci = ReturGudangDetail::updateOrCreate(
                            [
                                'no_retur' => $noretur,
                                'kd_obat' => $key['kd_obat'],
                                'nopenerimaan' => $cekStok->nopenerimaan,
                                'no_batch' => $cekStok->nobatch,
                                'harga' => $cekStok->harga,
                            ],
                            [
                                'tgl_exp' => $cekStok->tglexp,
                                'jumlah_retur' => $sisa,
                                'alasan' => $key['alasan'],
                            ]
                        );
                        $rinciNya[] = $rinci;
                        $cekStok->update(['jumlah' => 0]);
                        // update stok gudand
                        $stGu = $stokGud->where('kdobat', $key['kd_obat'])
                            ->where('nopenerimaan', $cekStok->nopenerimaan)
                            ->where('nobatch', $cekStok->nobatch)
                            ->first();
                        if (!$stGu) {
                            $stgNb = $stokGud->where('kdobat', $key['kd_obat'])
                                ->where('nopenerimaan', $cekStok->nopenerimaan)
                                ->first();
                            if (!$stgNb) {
                                Stokrel::create(
                                    [
                                        'nopenerimaan' => $cekStok->nopenerimaan,
                                        'kdobat' => $cekStok->kdobat,
                                        'kdruang' => $request->gudang,
                                        'nobatch' => $cekStok->nobatch,
                                        'harga' => $cekStok->harga,
                                        'tglexp' => $cekStok->tglexp,
                                        'tglpenerimaan' => $cekStok->tglpenerimaan,
                                        'jumlah' => $cekStok->jumlah,

                                    ]
                                );
                                // $masuk = $sisax;
                                // return new JsonResponse([
                                //     'message' => 'Stok ' . $key['nama_obat'] . ' dengan nomor penerimaan ' . $cekStok->nopenerimaan . ' tidak ditemukan di gudang'
                                // ], 410);
                            } else {
                                $tot = $stgNb->jumlah + $sisa;
                                $stgNb->update(['jumlah' => $tot]);
                                // $masuk = $sisax;
                            }
                        } else {
                            $tot = $stGu->jumlah + $sisa;
                            $stGu->update(['jumlah' => $tot]);
                        }
                        $masuk = $sisax;
                    } else {
                        $sisax = $sisa - $masuk;
                        $rinci = ReturGudangDetail::updateOrCreate(
                            [
                                'no_retur' => $noretur,
                                'kd_obat' => $key['kd_obat'],
                                'nopenerimaan' => $cekStok->nopenerimaan,
                                'no_batch' => $cekStok->nobatch,
                                'harga' => $cekStok->harga,
                            ],
                            [
                                'tgl_exp' => $cekStok->tglexp,
                                'jumlah_retur' => $masuk,
                                'alasan' => $key['alasan'],
                            ]
                        );
                        $rinciNya[] = $rinci;
                        $cekStok->update(['jumlah' => $sisax]);

                        // update stok gudand
                        $stGu = $stokGud->where('kdobat', $key['kd_obat'])
                            ->where('nopenerimaan', $cekStok->nopenerimaan)
                            ->where('nobatch', $cekStok->nobatch)
                            ->first();
                        if (!$stGu) {
                            $stgNb = $stokGud->where('kdobat', $key['kd_obat'])
                                ->where('nopenerimaan', $cekStok->nopenerimaan)
                                ->first();
                            if (!$stgNb) {
                                Stokrel::create(
                                    [
                                        'nopenerimaan' => $cekStok->nopenerimaan,
                                        'kdobat' => $cekStok->kdobat,
                                        'kdruang' => $request->gudang,
                                        'nobatch' => $cekStok->nobatch,
                                        'harga' => $cekStok->harga,
                                        'tglexp' => $cekStok->tglexp,
                                        'tglpenerimaan' => $cekStok->tglpenerimaan,
                                        'jumlah' => $masuk,

                                    ]
                                );
                            } else {
                                $tot = $stgNb->jumlah + $masuk;
                                $stgNb->update(['jumlah' => $tot]);
                            }
                            // $masuk = 0;
                        } else {
                            $tot = $stGu->jumlah + $masuk;
                            $stGu->update(['jumlah' => $tot]);
                        }
                        // Stokrel::where('id', $cekStok->id)
                        //     ->update(['jumlah' => $sisax]);

                        $masuk = 0;
                    }
                }
            }
            $user = FormatingHelper::session_user();
            $header = ReturGudang::updateOrCreate(
                [
                    'no_retur' => $noretur,
                ],
                [
                    'tgl_retur' => $request->tgl_retur . date(' H:m:s'),
                    'gudang' => $request->gudang,
                    'depo' => $request->depo,
                    'user_entry' => $user['kodesimrs'],
                    'kunci' => '1'
                ]
            );
            $header->load('details');
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Sudah Disimpan',
                'req' => $request->all(),
                'header' => $header,
                'kode' => $kode,
                'stok' => $stok,
                'tdkAdaStok' => $tdkAdaStok,
                'adaStok' => $adaStok,
                'noretur' => $noretur,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' => $e,
                'kode' => $kode ?? null,
                'cekStok' => $cekStok ?? null,
                'req' => $request->all(),
                'rinciNya' => $rinciNya,
                'str' => ' ' . $e
            ], 410);
        }
    }
    public static function setNomor($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 6 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("d") . "/" . date("m") . "/" . date("Y") . "-" . $kode;
    }
    public function lisRetur()
    {
        $head = ReturGudang::with([
            'details' => function ($det) {
                $det->select(
                    'no_retur',
                    'kd_obat',
                    'alasan',
                    DB::raw('sum(jumlah_retur) as jumlah_retur')
                )
                    ->with('masterobat:kd_obat,satuan_k,nama_obat,status_fornas,status_forkid,status_generik,status_kronis,status_prb')
                    ->groupBy('kd_obat', 'no_retur');
            },
            'depos:kode,nama',
            'gudangs:kode,nama',
            'user:kdpegsimrs,nama',
        ])
            ->where('no_retur', 'like', '%' . request('q') . '%')
            ->where('depo', '=', request('depo'))
            ->orderBy('tgl_retur', 'DESC')
            ->paginate(request('per_page'));

        $data = collect($head)['data'];
        $meta = collect($head)->except('data');

        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
        ]);
    }
}
