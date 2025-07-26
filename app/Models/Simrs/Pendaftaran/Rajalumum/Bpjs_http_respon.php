<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bpjs_http_respon extends Model
{
    use HasFactory;
    protected $table = 'bpjs_http_respon';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'request' => 'array',
        'respon' => 'array'
    ];
}
