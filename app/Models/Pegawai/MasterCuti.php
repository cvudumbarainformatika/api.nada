<?php

namespace App\Models\Pegawai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCuti extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function jenispegawai()
    {
        return $this->belongsTo(JenisPegawai::class);
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('jenispegawai', 'LIKE', '%' . $query . '%');
            // ->orWhere('nama', 'LIKE', '%' . $query . '%');
        });
    }
}
