<?php

namespace App\Models\Sigarang;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengguna extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'penanggungjawab', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('jabatan', 'LIKE', '%' . $query . '%');
        });
    }
}
