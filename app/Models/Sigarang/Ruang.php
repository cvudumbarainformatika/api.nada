<?php

namespace App\Models\Sigarang;

use App\Models\Satset\Satset;
use App\Models\Simrs\Organisasi\Organisasi;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    use HasFactory;
    // protected $connection = 'sigarang'; dipindah ke kepegx server 11
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function namagedung()
    {
        return $this->belongsTo(Gedung::class, 'gedung', 'nomor');
    }
    public function organisasi()
    {
        return $this->setConnection('mysql')->belongsTo(Organisasi::class, 'departement_uuid', 'satset_uuid');
    }
    public function satset()
    {
        return $this->setConnection('mysql')->belongsTo(Satset::class, 'satset_uuid', 'uuid');
    }

    public function mapingRuang()
    {
        return $this->hasOne(PenggunaRuang::class, 'kode', 'kode_ruang');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('uraian', 'LIKE', '%' . $query . '%')
                ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}
