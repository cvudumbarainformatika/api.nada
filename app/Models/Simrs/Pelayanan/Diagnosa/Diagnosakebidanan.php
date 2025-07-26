<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use App\Models\Simrs\Pelayanan\Intervensikebidanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosakebidanan extends Model
{
    use HasFactory;
    protected $table = 'diagnosakebidanan';
    protected $guarded = ['id'];

    public function intervensi()
    {
        return $this->hasMany(Intervensikebidanan::class, 'diagnosakebidanan_kode', 'id');
    }
}