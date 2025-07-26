<?php

namespace App\Models\Pegawai;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tandatangan extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function ptk()
    {
        return $this->belongsTo(Pegawai::class, 'ptk');
    }
    public function gudang()
    {
        return $this->belongsTo(Pegawai::class, 'gudang');
    }
    public function mengetahui()
    {
        return $this->belongsTo(Pegawai::class, 'mengetahui');
    }
    public function ppk()
    {
        return $this->belongsTo(Pegawai::class, 'ppk');
    }
}
