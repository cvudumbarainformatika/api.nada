<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;
    protected $table = 'rs19';
    protected $primaryKey = 'rs1';
    // protected $guarded = ['rs1']
    protected $fillable = [
        'rs1',
        'rs2',
        'rs3',
        'rs4',
        'rs5',
        'rs6',
        'rs7',
        'penunjang_lain',
        'panggil_antrian',
        'kode_ruang',
        'displaykode',
        'hidden',
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;


    public function kunjungan_poli()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs8', 'rs1');
    }
}
