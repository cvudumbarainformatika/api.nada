<?php

namespace App\Models\Simrs\Rekom;

use App\Models\KunjunganRawatInap;
use App\Models\Poli;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Mruanganranap;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekomdpjp extends Model
{
    use HasFactory;
    protected $table = 'rekom_dpjp';
    protected $guarded = ['id'];
    public $timestamps = false;

    // public function relkunjunganpoli()
    // {
    //     return $this->hasOneThrough(
    //         Mpoli::class,
    //         KunjunganPoli::class,
    //         'rs1', // Foreign key on the kunjungan poli table...
    //         'rs1', // Foreign key on the masterpoli table...
    //         'noreg', // Local key on the rekomdpjp table...
    //         'rs8' // Local key on the tabel kunjunganpoli table...
    //     );
    // }

    public function relkunjunganpoli()
    {
        return $this->belongsTo(KunjunganPoli::class, 'noreg', 'rs1');
    }

    // public function relkunjunganranap()
    // {
    //     return $this->hasOneThrough(
    //         Mruanganranap::class,
    //         KunjunganRawatInap::class,
    //         'rs1',
    //         'rs1',
    //         'noreg',
    //         'rs5'
    //     );
    // }

    public function relkunjunganranap()
    {
        return $this->belongsTo(
            KunjunganRawatInap::class,
            'noreg',
            'rs1'
        );
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }
    public function poli()
    {
        return $this->belongsTo(Poli::class, 'unit', 'rs1');
    }
}
