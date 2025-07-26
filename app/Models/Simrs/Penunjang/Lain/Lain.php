<?php

namespace App\Models\Simrs\Penunjang\Lain;

use App\Models\Poli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lain extends Model
{
    use HasFactory;
    protected $table = 'rs107';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function masterpenunjang()
    {
        return $this->hasOne(Poli::class, 'rs1', 'rs13');
    }
}
