<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang108 extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function maping()
    {
        return $this->hasOne(Maping108To50::class, 'kode108', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('uraian', 'LIKE', '%' . $query . '%')
                ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}
