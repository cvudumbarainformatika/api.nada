<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Masalrujukan extends Model
{
    use HasFactory;
    protected $table = 'rs6';
    protected $guarded = [];
    public $primarykey = 'rs1';

    public function scopeAsalrujukan($data)
    {
        return $data->select([
            'rs1 as kode',
            'rs2 as asalrujukan',
            'aktif as statusaktif',
            'kategori as kategori'
        ]);
    }

}
