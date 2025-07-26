<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;
    protected $table = 'rs21';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function dokter()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs1', 'rs9');
    }
}
