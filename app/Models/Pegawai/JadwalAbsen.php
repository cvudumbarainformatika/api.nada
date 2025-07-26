<?php

namespace App\Models\Pegawai;

use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalAbsen extends Model
{
    use HasFactory;

    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kategory()
    {
        return $this->belongsTo(Kategory::class);
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class);
    }

    public function pertama()
    {
        return $this->belongsTo(Hari::class, 'hari_01');
    }

    public function kedua()
    {
        return $this->belongsTo(Hari::class, 'hari_02');
    }
    public function ketiga()
    {
        return $this->belongsTo(Hari::class, 'hari_03');
    }
    public function keempat()
    {
        return $this->belongsTo(Hari::class, 'hari_04');
    }
    public function kelima()
    {
        return $this->belongsTo(Hari::class, 'hari_05');
    }
    public function keenam()
    {
        return $this->belongsTo(Hari::class, 'hari_06');
    }
    public function ketujuh()
    {
        return $this->belongsTo(Hari::class, 'hari_07');
    }

    public function jam01()
    {
        return $this->belongsTo(Jam::class, 'jam_01');
    }

    public function jam02()
    {
        return $this->belongsTo(Jam::class, 'jam_02');
    }
    public function jam03()
    {
        return $this->belongsTo(Jam::class, 'jam_03');
    }
    public function jam04()
    {
        return $this->belongsTo(Jam::class, 'jam_04');
    }
    public function jam05()
    {
        return $this->belongsTo(Jam::class, 'jam_05');
    }
    public function jam06()
    {
        return $this->belongsTo(Jam::class, 'jam_06');
    }
    public function jam07()
    {
        return $this->belongsTo(Jam::class, 'jam_07');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            // return $search->whereHas('user_id', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%');
            // ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // return $search->where('jenispegawai', 'LIKE', '%' . $query . '%');
            // ->orWhere('nama', 'LIKE', '%' . $query . '%');
            // });
        });
    }
}
