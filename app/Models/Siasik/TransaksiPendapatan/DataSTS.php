<?php

namespace App\Models\Siasik\TransaksiPendapatan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSTS extends Model
{
    use HasFactory;
    // protected $connection = 'rs_coba';
    protected $guarded = ['id'];
    protected $table = 'keu_trans_setor';
    public $timestamps = false;
    public function tbp()
    {
        return $this->hasMany(TBPHeader::class, 'noSetor', 'noSetor');
    }
    public function pendpatanlain()
    {
        return $this->hasMany(PendapatanLain::class, 'noSetor', 'noSetor');
    }
}
