<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Satuan extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sigarang';
    // protected $connection = 'mysql2';
    // protected $table = 'satuan_barang';
    // protected $fillable = [];

    // public function scopeFilter($search, array $reqs)
    // {
    //     $search->when($reqs['q'] ?? false, function ($search, $query) {
    //         return $search->where('satuanBarang', 'LIKE', '%' . $query . '%');
    //     });
    // }


    protected $guarded = ['id'];


    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%');
        });
    }
}
