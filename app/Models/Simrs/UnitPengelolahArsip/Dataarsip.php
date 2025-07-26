<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use App\Models\MorganisasiAdministrasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataarsip extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'data_arsip';
    protected $guarded = ['id'];

    public function unitpengolah()
    {
        return $this->hasOne(MorganisasiAdministrasi::class, 'kode', 'unit_pengolah');
    }
}
