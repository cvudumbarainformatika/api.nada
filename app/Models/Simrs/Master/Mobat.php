<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mobat extends Model
{
    use HasFactory;
    protected $table = 'rs32';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function scopeMobat($data)
    {
        return $data->select([
            'rs1 as kodeobat',
            'rs2 as namaobat'
        ]);
    }

    public function scopeFilter($cari,array $reqs)
    {
        $cari->when($reqs['q'] ?? false,
        function($data, $query){
            return $data->where('rs1', 'LIKE', '%' . $query . '%')
                ->orWhere('rs2', 'LIKE', '%' . $query . '%')
                ->orderBy('rs1');
        });
    }
}


