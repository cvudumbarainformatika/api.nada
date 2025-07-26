<?php

namespace App\Models\Simrs\SuratPasien;

use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ews\MapingProcedure;
use App\Models\Simrs\Master\MappingSnowmed;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratPasien extends Model
{
    use HasFactory;
    protected $table = 'rs23_nosurat';
    protected $guarded = ['id'];

    
}
