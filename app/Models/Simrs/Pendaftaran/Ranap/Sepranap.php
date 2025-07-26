<?php

namespace App\Models\Simrs\Pendaftaran\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sepranap extends Model
{
    use HasFactory;
    protected $table = 'rs227';
    protected $guarded = ['id'];

    public function scopeSepranap($data)
    {
        return $data->select(
            ['rs1 as noreg',
             'rs2 as norm',
             'rs3 as namaruang',
             'rs8 as nosep',
             'rs13 as noka',
             'rs6 as tglsep'
            ]
        );
    }

    public function scopeFilter($data)
    {
        // $cari->when($reqs['$request->noka'] ?? false,
        //         function($data, $query){
                    return $data->where('rs13', '=',  request(['noka']))
                        ->orderBy('rs1');
                // });
    }
}
