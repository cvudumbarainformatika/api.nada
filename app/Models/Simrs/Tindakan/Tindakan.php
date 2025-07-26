<?php

namespace App\Models\Simrs\Tindakan;

use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ews\MapingProcedure;
use App\Models\Simrs\Master\MappingSnowmed;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tindakan extends Model
{
    use HasFactory;
    protected $table = 'rs73';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = (int) $this->rs7 ? $this->rs7 : 0;
        $harga2 = (int)  $this->rs13 ? $this->rs13 : 0;
        $jumlah = (int) $this->rs5 ? $this->rs5 : 1;

        $hargatotal = (int)$harga1 + (int)$harga2;
        $subtotal = (int)$hargatotal * (int)$jumlah;
        //$subtotal = ($harga1+$harga2)*$jumlah;
        return ($subtotal);
    }

    public function maapingprocedure()
    {
        return $this->hasOne(MapingProcedure::class, 'kdMaster', 'rs4');
    }
    public function maapingsnowmed()
    {
        return $this->hasOne(MappingSnowmed::class, 'kdMaster', 'rs4');
    }

    public function mastertindakan()
    {
        return $this->hasOne(Mtindakan::class, 'rs1', 'rs4');
    }

    public function mpoli()
    {
        return $this->hasOne(Mpoli::class, 'rs1', 'rs22');
    }
    public function sambungan()
    {
        return $this->hasOne(TindakanSambung::class, 'rs73_id', 'id');
    }
    public function gambardokumens()
    {
        return $this->HasMany(Gbrdokumentindakan::class, 'rs73_id', 'id');
    }
    public function pegawai()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'rs9');
    }
    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'rs9');
    }
    public function pelaksanalamasimrs()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'rs8');
    }
}
