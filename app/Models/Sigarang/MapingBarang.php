<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingBarang extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];
    public function barang108()
    {
        return $this->belongsTo(Barang108::class, 'kode_108', 'kode');
        // return $this->belongsTo(Barang108::class, 'kode', 'kode_108');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
        // return $this->belongsTo(Satuan::class, 'kode', 'kode_satuan');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('barang108', function ($q) use ($query) {
                $q->where('uraian', 'like', '%' . $query . '%')
                    ->orWhere('kode', 'LIKE', '%' . $query . '%');
            })->orWhereHas('barangrs', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%')
                    ->orWhere('kode', 'LIKE', '%' . $query . '%');
            })->orWhereHas('satuan', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%')
                    ->orWhere('kode', 'LIKE', '%' . $query . '%');
            });
        });
    }
}
