<?php

namespace App\Models\Executive;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenerimaan extends Model
{
    use HasFactory;
    // protected $connection = 'kepex';
    protected $table = 'rs260';
    protected $guarded = ['id'];


    public function header_penerimaan()
    {
        return $this->belongsTo(HeaderPenerimaan::class, 'rs1', 'rs1');
    }
}
