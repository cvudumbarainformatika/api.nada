<?php

namespace App\Models\Simrs\Planing;

use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transrujukan extends Model
{
    use HasFactory;
    protected $table = 'rs288';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs1', 'poli');
    }

    public function relmpolix()
    {
        return $this->belongsTo(Mpoli::class, 'rs1', 'polirujukan');
    }

    public function rs141()
    {
        return $this->hasOne(WaktupulangPoli::class, 'rs1', 'rs1');
    }
    public function diagnosa()
    {
        return $this->hasOne(Diagnosa_m::class, 'rs1', 'rs10');
    }
}
