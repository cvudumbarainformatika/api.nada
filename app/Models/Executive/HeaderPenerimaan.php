<?php

namespace App\Models\Executive;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderPenerimaan extends Model
{
    use HasFactory;
    // protected $connection = 'kepex';
    protected $table = 'rs258';
    protected $guarded = ['id'];

    public function detail_penerimaan()
    {
        return $this->hasMany(DetailPenerimaan::class, 'rs1', 'rs1');
    }
    public function keu_trans_setor()
    {
        return $this->hasOne(KeuTransSetor::class, 'noSetor', 'noSetor');
    }

    // public function detail_keu_trans_setor()
    // {
    //     return $this->hasOneThrough(
    //         Pasien::class,
    //         KunjunganPoli::class,
    //         'rs1', // Foreign key on the kunjungan poli table...
    //         'rs1', // Foreign key on the pasien table...
    //         'rs1', // Local key on rs258 table...
    //         'rs2' // Local key on the keu_trans_setor table...
    //     );
    // }
}
