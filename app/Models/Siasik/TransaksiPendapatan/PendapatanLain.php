<?php

namespace App\Models\Siasik\TransaksiPendapatan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendapatanLain extends Model
{
    use HasFactory;
    // protected $connection = 'rs_coba';
    protected $guarded = ['id'];
    protected $table = 'rs258';
    public $timestamps = false;
    public function plainlain()
    {
        return $this->belongsTo(PendapatanLainRinci::class, 'rs1', 'rs1');
    }
}
