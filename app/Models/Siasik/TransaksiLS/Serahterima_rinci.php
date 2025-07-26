<?php

namespace App\Models\Siasik\TransaksiLS;

use App\Models\Siasik\Master\Akun_jurnal;
use App\Models\Siasik\Master\Akun_mapjurnal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serahterima_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'serahterima50';
    public $timestamps = false;
    public function jurnal()
    {
        return $this->belongsTo( Akun_mapjurnal::class, 'koderek50', 'kodeall');
    }
}
