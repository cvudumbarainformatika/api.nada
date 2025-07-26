<?php

namespace App\Models\Pegawai;

use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiAbsen extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function kategory()
    {
        return $this->belongsTo(Kategory::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }
}
