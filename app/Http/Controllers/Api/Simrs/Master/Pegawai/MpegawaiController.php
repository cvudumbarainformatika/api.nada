<?php

namespace App\Http\Controllers\Api\Simrs\Master\Pegawai;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpegawaiController extends Controller
{
    public function index(){
        $req = [
            'order_by' => request('order_by', 'created_at'),
            'sort' => request('sort', 'asc'),
            'page' => request('page', 1),
            'per_page' => request('per_page', 10),
        ];

        $query = Pegawai::query()
            ->when(request('q'), function ($q) {
                $q->where(function ($query) {
                    $query->where('nama', 'like', '%' . request('q') . '%');
                });
            })
            ->where('aktif','<>', '1')
            ->orderBy($req['order_by'], $req['sort']);
        $totalCount = (clone $query)->count();
        $data = $query->simplePaginate($req['per_page']);

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'nip' => 'required',
            'nik' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'kelamin' => 'required',
            'templahir' => 'required',
            'tgllahir' => 'required',
            'jabatan' => 'required',
            'pass' => 'required',
            'telp' => 'required',
            'email' => 'required',
            'statusspesialis' => 'required',
            'kdgroupnakes' => 'required',
            'kddpjp' => 'nullable',
            'kdruangansim' => 'nullable',
            'satset_uuid' => 'nullable'
        ], [
            'nip.required' => 'NIP wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'nama.required' => 'Nama wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'kelamin.required' => 'Kelamin wajib diisi.',
            'templahir.required' => 'Tempat Lahir wajib diisi.',
            'tgllahir.required' => 'Tanggal Lahir wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'pass.required' => 'Password wajib diisi.',
            'telp.required' => 'Telepon wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'statusspesialis.required' => 'Status Spesialis wajib diisi.',
            'kdgroupnakes.required' => 'Group Nakes wajib diisi.',

        ]);

        $password = bcrypt($validated['pass']);
         $pegawai = Pegawai::updateOrCreate(
            ['nip' => $validated['nip']],
            [
                'nik' => $validated['nik'],
                'nip' => $validated['nip'],
                'nama' => $validated['nama'],
                'alamat' => $validated['alamat'],
                'kelamin' => $validated['kelamin'],
                'templahir' => $validated['templahir'],
                'tgllahir' => $validated['tgllahir'],
                'jabatan' => $validated['jabatan'],
                'aktif' => '',
                'pass' => $validated['pass'],
                'telp' => $validated['telp'],
                'email' => $validated['email'],
                'account_pass' => $password,
                'statusspesialis' => $validated['statusspesialis'],
                'kdgroupnakes' => $validated['kdgroupnakes'],
                'kddpjp' => $validated['kddpjp'],
                'kdruangansim' => $validated['kdruangansim'],
                'satset_uuid' => $validated['satset_uuid'],
            ],
        );
        $update = Pegawai::where('nip', $validated['nip'])->first();
        $update->kdpegsimrs = $update->id;
        $update->save;
        return new JsonResponse([
            'data' => $pegawai,
            'message' => 'Data Pegawai berhasil disimpan'
        ]);
    }
}
