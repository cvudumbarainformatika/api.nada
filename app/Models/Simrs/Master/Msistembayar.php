<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Msistembayar extends Model
{
    use HasFactory;
    protected $table = 'rs9';
    protected $fillable = ['rs1', 'rs2','rs3','rs4', 'rs5','rs6','rs7','rs8','rs10','pelayanan', 'groups', 'hidden', 'rs9'];

    protected $primaryKey = 'rs1'; // ← ini wajib!
    public $incrementing = false;  // ← kalau rs1 bukan auto-increment
    protected $keyType = 'string'; // ← kalau rs1 berupa string,

    // protected $guarded=['rs1'];
}
