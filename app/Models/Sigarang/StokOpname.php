<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    // digunakan sebagai table penyesuaian stok
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function opname()
    {
        return $this->belongsTo(MonthlyStokUpdate::class);
    }

    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
    }
    public function barang108()
    {
        return  $this->belongsTo(Barang108::class, 'kode_rs', 'kode');
    }
    public function gudang()
    {
        return  $this->belongsTo(Gudang::class, 'kode_tempat', 'kode');
    }
}
