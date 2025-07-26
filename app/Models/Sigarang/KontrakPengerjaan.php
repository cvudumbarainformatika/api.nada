<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KontrakPengerjaan extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'kontrakPengerjaan_header';
    // protected $fillable = [];
    protected $guarded = [];
    public $timestamps = false;


    public function penyedia()
    {
        return $this->belongsTo(Supplier::class, 'kodeperusahaan', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nokontrakx', 'LIKE', '%' . $query . '%')
                ->orWhere('namaperusahaan', 'LIKE', '%' . $query . '%');
            // ->orWhere('kodemapingrs', 'LIKE', '%' . $query . '%');
        });
    }
}
