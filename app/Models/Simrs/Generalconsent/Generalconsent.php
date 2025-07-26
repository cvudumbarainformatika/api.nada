<?php

namespace App\Models\Simrs\Generalconsent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Generalconsent extends Model
{
    use HasFactory;
    protected $table = 'gencons';
    protected $guarded = ['id'];
    protected $appends = ['ttdpasien_url', 'ttdpetugas_url'];


    public function getTtdpasienUrlAttribute()
    {
        // $image = public_path('storage/' . $this->attributes['ttdpasien']);
        // $base64 = base64_encode(file_get_contents($image));
        // return $base64;
        $image = URL::to('/storage/' . $this->attributes['ttdpasien']);
        // if (file_exists($image)) {
        //     $base64 = base64_encode(file_get_contents($image));
        //     return $this->attributes['ttdpasien'] ? $base64 : null;
        // } else {
        //     return null;
        // }
        $handle = @fopen($image, 'r');
        if ($handle) {
            $base64 = 'data:image/jpg;base64,' . base64_encode(file_get_contents($image));
            return $this->attributes['ttdpasien'] ? $base64 : null;
        } else {
            return null;
        }
    }
    public function getTtdpetugasUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->attributes['ttdpetugas']);
        $handle = @fopen($image, 'r');
        if ($handle) {
            $base64 = 'data:image/jpg;base64,' . base64_encode(file_get_contents($image));
            return $this->attributes['ttdpetugas'] ? $base64 : null;
        } else {
            return null;
        }
    }
}
