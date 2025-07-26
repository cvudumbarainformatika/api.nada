<?php

namespace App\Models\Simrs\Bpjs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsAntrian extends Model
{
    use HasFactory;
    protected $table = 'bpjs_antrian';
    protected $guarded = ['id'];

    public static function scopeGetAll($query)
    {
        $query
            ->from('bpjs_pasien_baru as a')
            ->select([
                'a.nomorkartu',
                'a.tanggallahir',
                'a.nik'
            ]);
    }

    public static function scopeGetByNoBpjs($query, $noBpjs)
    {
        $query
            ->getAll()
            ->where('a.nomorkartu', $noBpjs);
    }
}
