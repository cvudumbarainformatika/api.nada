<?php

namespace App\Models\Simrs\jenazah;

use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class billjenazah extends Model
{
    use HasFactory;
    protected $table = 'rs279';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

}
