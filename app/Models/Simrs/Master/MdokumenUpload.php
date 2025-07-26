<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MdokumenUpload extends Model
{
    use HasFactory;
    protected $table = 'm_upload_dok_luar';
    protected $guarded = ['id'];
}
