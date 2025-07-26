<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    use HasFactory;
    protected $connection = 'antrean';
    protected $table = 'layanans';
    protected $guarded = ['id'];

    public function unit()
    {
        return $this->hasMany(Unit::class, 'layanan_id', 'id_layanan');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'layanan_id', 'id_layanan');
    }
}
