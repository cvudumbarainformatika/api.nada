<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\JenisPegawai;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\PegawaiTanpaAppendFoto;
use App\Models\Pegawai\Prota;
use App\Models\Pegawai\Ruangan;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery\Undefined;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransaksiAbsenController extends Controller
{
    //

    public function rekap()
    {
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $thisMonth = request('bulan') ? request('bulan') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $user = User::where('id', '>', 1)
            ->filter(request(['q']))
            ->oldest('id')
            ->with(['absens' => function ($query) use ($thisMonth, $thisYear) {
                $query->whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
                    ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31');
            }])
            // ->simplePaginate($per_page);
            ->paginate($per_page);
        $userCollections = collect($user);

        $dataUser = $userCollections->only('data');
        $dataUser->all();
        $meta = $userCollections->except('data');
        $meta->all();
        $data = [];
        foreach ($user as $key) {
            $absen = $key->absens;
            foreach ($absen as $value) {
                // return new JsonResponse($value);
                // if ($value['masuk'] === null || $value['masuk'] === '') {
                //     return new JsonResponse($value);
                // }
                if ($value['masuk'] !== null) {
                    $temp = explode('-', $value['tanggal']);
                    // $temp = explode('-', $value->tanggal);
                    $day = $temp[2];
                    // $day = $this->getDayName($temp[2]);
                    $value['day'] = $day;
                    $toIn = explode(':', $value['kategory']->masuk);
                    $act = explode(':', $value['masuk']);
                    $jam = (int)$act[0] - (int)$toIn[0];
                    $menit =  (int)$act[1] - (int)$toIn[1];
                    $detik =  (int)$act[2] - (int)$toIn[2];

                    if ($jam > 0 || $menit > 00) {
                        $value['terlambat'] = 'yes';
                    } else {
                        $value['terlambat'] = 'no';
                    }
                    $dMenit = $menit >= 10 ? $menit : '0' . $menit;
                    $dDetik = $detik >= 10 ? $detik : '0' . $detik;
                    $diff = $jam . ':' . $dMenit . ':' . $dDetik;
                    $value['diff'] = $diff;
                }
            }

            $data[$key['id']] = $absen;
        }
        // return new JsonResponse($data);

        $apem = [];
        foreach ($data as $key => $value) {
            // return new JsonResponse($value);
            $telat = $value->where('terlambat', 'yes')->count();
            $total = $value->where('terlambat')->count();
            $userapem = null;
            foreach ($value as $ni) {
                $userapem = $ni->user_id;
            }
            // $userapem->all();
            // $userapem->only('user_id');
            // $key['value'] = $key;
            array_push($apem, ['total' => $total, 'telat' => $telat, 'user_id' => $userapem]);
        }
        $data['apem'] = $apem;
        $data['meta'] = $meta;
        $data['user'] = $dataUser;
        return new JsonResponse($data);
    }
    public function index()
    {
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $thisMonth = request('bulan') ? request('bulan') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $user = User::where('id', '>', 3)->oldest('id')->filter(request(['q']))->paginate($per_page);
        $userCollections = collect($user);
        $meta = $userCollections->except('data');
        $meta->all();
        $users = $userCollections->only('data');
        $users->all();
        // $temp = [
        //     'data' => $users,
        //     'meta' => $meta,
        // ];
        // return new JsonResponse($temp);
        $data = [];
        foreach ($users['data'] as $key) {
            // return new JsonResponse($key);
            $absen = TransaksiAbsen::whereDate('tanggal', '>=', $thisYear . '-' . $thisMonth . '-01')
                ->whereDate('tanggal', '<=', $thisYear . '-' . $thisMonth . '-31')
                ->where('user_id', $key['id'])
                ->with('user', 'kategory')
                ->get();
            // return new JsonResponse($absen);
            $tanggals = [];
            foreach ($absen as $value) {
                // return new JsonResponse($value);
                $temp = explode('-', $value['tanggal']);
                // $temp = explode('-', $value->tanggal);
                $day = $temp[2];
                // $day = $this->getDayName($temp[2]);
                $value['day'] = $day;

                $toIn = explode(':', $value['kategory']->masuk);
                $act = explode(':', $value['masuk']);
                $jam = (int)$act[0] - (int)$toIn[0];
                $menit =  (int)$act[1] - (int)$toIn[1];
                $detik =  (int)$act[2] - (int)$toIn[2];

                if ($jam > 0 || $menit > 40) {
                    $value['terlambat'] = 'yes';
                } else {
                    $value['terlambat'] = 'no';
                }
                $dMenit = $menit >= 10 ? $menit : '0' . $menit;
                $dDetik = $detik >= 10 ? $detik : '0' . $detik;
                $diff = $jam . ':' . $dMenit . ':' . $dDetik;
                $value['diff'] = $diff;
            }

            $data[$key['id']] = $absen;
            // array_push($data, [$key['id'] => $temp]);
        }
        // return new JsonResponse($data);
        // $tanggals = [];
        // foreach ($data as $key) {
        //     return new JsonResponse($key);
        //     $temp = explode('-', $key['tanggal']);
        //     // $temp = explode('-', $key->tanggal);
        //     $day = $temp[2];
        //     // $day = $this->getDayName($temp[2]);
        //     $key['day'] = $day;

        //     $toIn = explode(':', $key['kategory']->masuk);
        //     $act = explode(':', $key['masuk']);
        //     $jam = (int)$act[0] - (int)$toIn[0];
        //     $menit =  (int)$act[1] - (int)$toIn[1];
        //     $detik =  (int)$act[2] - (int)$toIn[2];

        //     if ($jam > 0 || $menit > 40) {
        //         $key['terlambat'] = 'yes';
        //     } else {
        //         $key['terlambat'] = 'no';
        //     }
        //     $dMenit = $menit >= 10 ? $menit : '0' . $menit;
        //     $dDetik = $detik >= 10 ? $detik : '0' . $detik;
        //     $diff = $jam . ':' . $dMenit . ':' . $dDetik;
        //     $key['diff'] = $diff;
        // }

        // $collects = collect($data);
        // $userGroup = $collects->groupBy('user_id');
        $apem = [];
        foreach ($data as $key => $value) {
            // return new JsonResponse($value);
            $telat = $value->where('terlambat', 'yes')->count();
            $total = $value->where('terlambat')->count();
            $userapem = null;
            foreach ($value as $ni) {
                $userapem = $ni->user_id;
            }
            // $userapem->all();
            // $userapem->only('user_id');
            // $key['value'] = $key;
            array_push($apem, ['total' => $total, 'telat' => $telat, 'user_id' => $userapem]);
        }
        $data['apem'] = $apem;
        // foreach ($apem as &$key) {
        //     array_push($data[$key['user_id']], $key);
        // }




        return new JsonResponse($data, 200);
        // return new JsonResponse([
        //     'data' => $userGroup,
        //     'telat' => $telat,
        // ], 200);
    }

    public function getAbsenToday()
    {
        $user = JWTAuth::user();
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '=', date('Y-m-d'))
            ->first();
        if (!$data) {

            return new JsonResponse(['message' => 'tidak ada data'], 500);
        }

        return new JsonResponse($data, 200);
    }

    public function getRekapByUser()
    {
        $user = JWTAuth::user();
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->with('kategory')
            ->latest()
            ->get();
        return new JsonResponse($data);
    }
    public function getRekapByUserLibur()
    {
        $user = JWTAuth::user();
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');
        $per_page = request('per_page') ? request('per_page') : 10;
        $masuk = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->with('kategory')
            ->latest()
            ->get();


        $data['masuk'] = $masuk;
        $libur = Libur::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->latest()
            ->get();

        $data['libur'] = $libur;
        return new JsonResponse($data);
    }


    public function getRekapPerUser()
    {
        $user = User::find(request('id'));
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');
        $from = $thisYear . '-' . $month . '-01';
        $to = $thisYear . '-' . $month . '-31';
        // $per_page = request('per_page') ? request('per_page') : 10;
        $prota = Prota::where('tgl_libur', '>=', $from)
            ->where('tgl_libur', '<=', $to)
            ->get();
        $libur = Libur::where('tanggal', '>=', $from)
            ->where('tanggal', '<=', $to)
            ->where('user_id', $user->id)
            ->with('user')
            ->get();
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $from)
            ->whereDate('tanggal', '<=', $to)
            ->orderBy(request('order_by'), request('sort'))
            ->with('kategory')
            ->get();
        $tanggals = [];
        foreach ($data as $key) {
            $temp = date('Y/m/d', strtotime($key['tanggal']));
            $week = date('W', strtotime($key['tanggal']));
            $toIn = explode(':', $key['kategory']->masuk);
            $act = explode(':', $key['masuk']);
            $jam = (int)$act[0] - (int)$toIn[0];
            $menit =  (int)$act[1] - (int)$toIn[1];
            $detik =  (int)$act[2] - (int)$toIn[2];

            if ($jam > 0 || $menit > 10) {
                $key['terlambat'] = 'yes';
            } else {
                $key['terlambat'] = 'no';
            }
            $dMenit = $menit >= 10 ? $menit : '0' . $menit;
            $dDetik = $detik >= 10 ? $detik : '0' . $detik;
            $diff = $jam . ':' . $dMenit . ':' . $dDetik;
            $key['diff'] = $diff;
            $key['week'] = $week;
            array_push($tanggals, $temp);
        };
        $collects = collect($data);
        $grouped = $collects->groupBy('week');
        $telat = $collects->where('terlambat', 'yes')->count();
        return new JsonResponse([
            'libur' => $libur,
            'prota' => $prota,
            'telat' => $telat,
            'weeks' => $grouped,
            'tanggals' => $tanggals,
            'data' => $data,
        ], 200);
    }

    public function getDayName($day)
    {
        $temp = '';
        switch ($day) {
            case '01':
                $temp = 'satu';
                break;
            case '02':
                $temp = 'dua';
                break;
            case '03':
                $temp = 'tiga';
                break;
            case '04':
                $temp = 'empat';
                break;
            case '05':
                $temp = 'lima';
                break;
            case '06':
                $temp = 'enam';
                break;
            case '07':
                $temp = 'tujuh';
                break;
            case '08':
                $temp = 'delapan';
                break;
            case '09':
                $temp = 'sembilan';
                break;
            case '10':
                $temp = 'sepuluh';
                break;
            case '11':
                $temp = 'sebelas';
                break;
            case '12':
                $temp = 'duabelas';
                break;
            case '13':
                $temp = 'tigabelas';
                break;
            case '14':
                $temp = 'empatbelas';
                break;
            case '15':
                $temp = 'limabelas';
                break;
            case '16':
                $temp = 'enambelas';
                break;
            case '17':
                $temp = 'tujuhbelas';
                break;
            case '18':
                $temp = 'delapanbelas';
                break;
            case '19':
                $temp = 'sembilanbelas';
                break;
            case '20':
                $temp = 'duapuluh';
                break;
            case '21':
                $temp = 'duapuluhsatu';
                break;
            case '22':
                $temp = 'duapuluhdua';
                break;
            case '23':
                $temp = 'duapuluhtiga';
                break;
            case '24':
                $temp = 'duapuluhempat';
                break;
            case '25':
                $temp = 'duapuluhlima';
                break;
            case '26':
                $temp = 'duapuluhenam';
                break;
            case '27':
                $temp = 'duapuluhtujuh';
                break;
            case '28':
                $temp = 'duapuluhdelapan';
                break;
            case '29':
                $temp = 'duapuluhsembilan';
                break;
            case '30':
                $temp = 'tigapuluh';
                break;
            case '31':
                $temp = 'tigapuluhsatu';
                break;

            default:
                'enol';
                break;
        }
        return $temp;
    }


    public function autocomplete()
    {
        $ruangan = Ruangan::all();
        $jenis = JenisPegawai::all();
        $data = [
            'ruangan' => $ruangan,
            'jenis_pegawai' => $jenis
        ];
        return response()->json($data);
    }
    public function prota()
    {
        $periode = request('periode');
        $split = explode("-", $periode);
        $year = $split[0];
        $month = $split[1];
        $prota = Prota::whereMonth('tgl_libur', $month)
            ->whereYear('tgl_libur', $year)->get();
        return response()->json($prota);
    }
    public function rekapan_absen_perbulan()
    {
        $periode = request('periode');

        // return $periode;

        $data = Pegawai::select('id','nip','nik','nama','kelamin','foto','ttdpegawai','kdpegsimrs','jenispegawai','jabatan','ruang','flag','alamat','aktif')
            ->where('aktif', '=', 'AKTIF')
            // ->where('account_pass', '=', null)
            ->where(function ($query) {
                $query->when(request('flag') ?? false, function ($search, $q) {
                    return $search->where('flag', '=', $q);
                });
                $query->when(request('ruang') ?? false, function ($search, $q) {
                    return $search->where('ruang', '=', $q);
                });
            })
            ->filter(request(['q']))
            ->with([
                "transaksi_absen.kategory", "jenis_pegawai", "relasi_jabatan", "ruangan", "transaksi_absen" => function ($q) use ($periode) {
                    // $split = explode("-", $periode);
                    // $year = $split[0];
                    // $month = $split[1];
                    // $q->whereMonth('created_at', $month)
                    //     ->whereYear('created_at', $year);
                    $q->where('created_at', 'like', $periode . '-%');
                }, "user.libur" => function ($q) use ($periode) {
                    // $split = explode("-", $periode);
                    // $year = $split[0];
                    // $month = $split[1];
                    // $q->whereMonth('tanggal', $month)
                    //     ->whereYear('tanggal', $year);
                    $q->where('tanggal', 'like', $periode . '-%');
                }, "alpha" => function ($q) use ($periode) {
                    // $split = explode("-", $periode);
                    // $year = $split[0];
                    // $month = $split[1];
                    // $q->whereMonth('tanggal', $month)
                    //     ->whereYear('tanggal', $year);
                    $q->where('tanggal', 'like', $periode . '-%');
                }
            ])
            // ->orderBy(request('order_by'), request('sort'))
            ->orderBy('flag', 'ASC')
            ->orderBy('nama', 'ASC')
            ->paginate(request('per_page'));
        // $data->setAppends([]);
        return response()->json($data);
    }

    public function print_absen_perbulan()
    {
        $periode = request('periode');

        $data = Pegawai::where('aktif', '=', 'AKTIF')
            ->where(function ($query) {
                $query->when(request('flag') ?? false, function ($search, $q) {
                    return $search->where('flag', '=', $q);
                });
                $query->when(request('ruang') ?? false, function ($search, $q) {
                    return $search->where('ruang', '=', $q);
                });
            })
            ->filter(request(['q']))
            ->with([
                "transaksi_absen.kategory", "jenis_pegawai", "relasi_jabatan", "ruangan", "transaksi_absen" => function ($q) use ($periode) {
                    $split = explode("-", $periode);
                    $year = $split[0];
                    $month = $split[1];
                    $q->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year);
                }, "user.libur" => function ($q) use ($periode) {
                    $split = explode("-", $periode);
                    $year = $split[0];
                    $month = $split[1];
                    $q->whereMonth('tanggal', $month)
                        ->whereYear('tanggal', $year);
                }, "alpha" => function ($q) use ($periode) {
                    $split = explode("-", $periode);
                    $year = $split[0];
                    $month = $split[1];
                    $q->whereMonth('tanggal', $month)
                        ->whereYear('tanggal', $year);
                }
            ])
            // ->orderBy(request('order_by'), request('sort'))
            ->orderBy('flag', 'ASC')
            ->orderBy('nama', 'ASC')
            ->get();
        return response()->json($data);
    }
}
