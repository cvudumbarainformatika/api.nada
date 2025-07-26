<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maping108To50 extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'map108to50';
    protected $fillable = [];

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('uraian108', 'LIKE', '%' . $query . '%')
                ->orWhere('kode108', 'LIKE', '%' . $query . '%');
        });
    }
}
