<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Penunjang\Farmasinew\Template\KamarOperasiDetailTemplate;
use App\Models\Simrs\Penunjang\Farmasinew\Template\KamarOperasiTemplate;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateObatOperasiController extends Controller
{
    //
    public function cari()
    {
        $data = KamarOperasiTemplate::where('nama', 'like', '%' . request('q') . '%')
            ->when(request('sistembayar') != 'all', function ($q) {
                $q->where('sistembayar', request('sistembayar'));
            })
            // ->where('sistembayar', request('sistembayar'))
            ->when(request('user') == 'private', function ($q) {
                $q->where('user', 'private')
                    ->where('pegawai_id', auth()->user()->pegawai_id);
            }, function ($q) {
                $q->where(function ($y) {
                    $y->where('user', 'public')
                        ->orWhere(function ($x) {
                            $x->where('user', 'private')
                                ->where('pegawai_id', auth()->user()->pegawai_id);
                        });
                });
            })
            ->with(['rinci.obat:kd_obat,nama_obat', 'pegawai:id,nama'])
            ->limit(20)
            ->get();
        return new JsonResponse([
            'message' => 'data ditemukan',
            'data' => $data
        ]);
    }
    public function simpan(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $id = $request->id ?? null;

            $data = KamarOperasiTemplate::updateOrCreate(
                [
                    'id' => $id
                ],
                [
                    'nama' => $request->nama,
                    'user' => $request->user,
                    'pegawai_id' => auth()->user()->pegawai_id,
                    'sistembayar' => $request->groups
                ]
            );
            if (!$data) {
                throw new \Exception('ada kesalahan, gagal menyimpan data');
            }

            $detail = KamarOperasiDetailTemplate::updateOrCreate(
                [
                    'kamar_operasi_template_id' => $data->id,
                    'kd_obat' => $request->kd_obat,
                ],
                [
                    'jumlah' => $request->jumlah
                ]
            );
            if (!$detail) {
                throw new \Exception('ada kesalahan, gagal menyimpan obat');
            }
            $data->load('rinci.obat:kd_obat,nama_obat', 'pegawai:id,nama');
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Template Obat Operasi Sudah Disiampan',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ], 410);
        }
    }
    public function hapusRinci(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = KamarOperasiDetailTemplate::find($request->id);
            if (!$data) {
                throw new \Exception('data tidak ditemukan');
            }
            $idTem = $data->kamar_operasi_template_id;

            $data->delete();
            $jum = KamarOperasiDetailTemplate::where('kamar_operasi_template_id', '=', $idTem)->count();
            if ($jum == 0) {
                KamarOperasiTemplate::find($idTem)->delete();
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Obat Sudah Dihapus',
            ]);
        } catch (\Throwable $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ], 410);
        }
    }

    public function kirimOrder(Request $request)
    {
        // perlu di cek apakah simtem bayar pasien sudah sama dengan yang di template atau belum
        if ($request->groupsistembayar == '1' && $request->sistembayar != '1') return new JsonResponse([
            'message' => 'Pasien BPJS tidak boleh menggunkanan Template non-BPJS'
        ], 410);
        try {
            DB::connection('farmasi')->beginTransaction();
            $message = 'Data sudah dikirim ke depo';
            $status = 200;
            $rinci = collect($request->rinci)->map(function ($item) {
                return $item['kd_obat'];
            })->toArray();
            if ($request->sistembayar == '1') $sistemBayar = ['SEMUA', 'BPJS'];
            else $sistemBayar = ['SEMUA', 'UMUM'];
            $cariobat = Stokreal::select(
                'stokreal.kdobat as kd_obat',
                DB::raw('sum(stokreal.jumlah) as total')
            )
                ->with(
                    [
                        // 'minmax',
                        'persiapanrinci' => function ($res) {
                            $res->select(
                                'persiapan_operasi_rincis.kd_obat',
                                DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                            )
                                ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                                ->whereIn('persiapan_operasis.flag', ['', '1'])
                                ->groupBy('persiapan_operasi_rincis.kd_obat');
                        },
                        'permintaanobatrinci' => function ($permintaanobatrinci) {
                            $permintaanobatrinci->select(
                                'permintaan_r.no_permintaan',
                                'permintaan_r.kdobat',
                                DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                            )
                                ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                // biar yang ada di tabel mutasi ga ke hitung
                                ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                    $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                        ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                })
                                ->whereNull('mutasi_gudangdepo.kd_obat')

                                ->where('permintaan_h.tujuan', request('kdruang'))
                                ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                ->groupBy('permintaan_r.kdobat');
                        },
                    ]
                )
                ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
                ->where('stokreal.kdruang', 'Gd-04010103')
                ->where('stokreal.jumlah', '>', 0)
                ->whereIn('new_masterobat.sistembayar', $sistemBayar)
                ->whereIn('new_masterobat.kd_obat', $rinci)
                ->groupBy('stokreal.kdobat')
                ->get();
            $stok = collect($cariobat)->map(function ($x, $y) {
                $total = $x->total ?? 0;
                $jumlahper = $x['persiapanrinci'][0]->jumlah ?? 0;
                $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0;
                $x->alokasi = (float)$total -  (float)$jumlahper - (float)$permintaanobatrinci;
                return $x;
            });
            $rwOb = Mobatnew::select('kd_obat', 'status_konsinyasi')->whereIn('kd_obat', $rinci)->get();
            $obat = collect($rwOb);
            $data = $request->all();
            $untukSimpanRinci = [];
            foreach ($data['rinci'] as $key => $value) {
                $data['rinci'][$key]['alokasi'] = $stok->where('kd_obat', $value['kd_obat'])->first()->alokasi ?? 0;
                $data['rinci'][$key]['status_konsinyasi'] = $obat->where('kd_obat', $value['kd_obat'])->first()->status_konsinyasi ?? '0';
                if ($data['rinci'][$key]['alokasi'] < $data['rinci'][$key]['jumlah']) {
                    $data['rinci'][$key]['errMsg'] = 'Alokasi Tidak Mencukupi';
                    $message = 'Ada Alokasi yang tidak mencukupi, belum dikirim ke depo';
                    $status = 410;
                } else {
                    $untukSimpanRinci[] = [
                        'kd_obat' => $data['rinci'][$key]['kd_obat'],
                        'jumlah_minta' => $data['rinci'][$key]['jumlah'],
                        'status_konsinyasi' => $data['rinci'][$key]['status_konsinyasi']
                    ];
                    $data['rinci'][$key]['successMsg'] = 'Sudah Disimpan';
                }
            }
            if (sizeof($untukSimpanRinci) > 0) {
                $user = FormatingHelper::session_user();
                $kode = $user['kodesimrs'];
                $procedure = 'persiapanok(@nomor)';
                $colom = 'persiapanok';
                $lebel = 'OP-KO';
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $nopermintaan = FormatingHelper::resep($wew, $lebel);
                $flag = '1';
                $siapRinci = [];
                if (sizeof($untukSimpanRinci) < sizeof($data['rinci'])) $flag = '';
                $head = PersiapanOperasi::updateOrCreate(
                    [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'nopermintaan' => $nopermintaan,
                    ],
                    [
                        'tgl_permintaan' => date('Y-m-d H:i:s'),
                        'user_minta' => $kode,
                        'flag' => $flag,
                    ]
                );
                foreach ($untukSimpanRinci as $key) {
                    $key['nopermintaan'] = $nopermintaan;
                    $key['created_at'] = date('Y-m-d H:i:s');
                    $key['updated_at'] = date('Y-m-d H:i:s');
                    $siapRinci[] = $key;
                }
                $rinci = PersiapanOperasiRinci::insert($siapRinci);

                $head->load('rinci.obat:kd_obat,nama_obat');
            } else {
                $status = 442;
            }


            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => $message,
                'rinci' => $rinci,
                'stok' => $stok,
                'data' => $data,
                'status' => $status,
                'head' => $head ?? null,
                'nopermintaan' => $nopermintaan ?? null,
                'siapRinci' => $siapRinci ?? null,
                'req' => $request->all(),
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'req' => $request->all(),
            ], 410);
        }
    }
}
