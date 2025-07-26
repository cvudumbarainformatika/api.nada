<?php

namespace App\Models\Simrs\InformConcern;

use App\Models\KunjunganRawatInap;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Intervention\Image\ImageManager;

class InformConcern extends Model
{
    use HasFactory;
    protected $table = 'inform_concern';
    protected $guarded = ['id'];

    protected $casts = [
        'diagnosis' => 'array',
        'tujuan' => 'array',
        'resiko' => 'array',
        'prognosis' => 'array',

        'komplikasi' => 'array',
        'tindakanMedis' => 'array',
        'tatacara' => 'array',
        'tujuan'=>'array',
        'resiko'=>'array'
    ];

    // protected $appends = ['ttd_dokter_url', 'ttd_petugas_url','ttd_saksi_pasien_url','ttd_yg_menyatakan_url'];

    public function getTtdDokterUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->ttdDokter);
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            // $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($image));
            $manager = new ImageManager();
            $base64 = (string) $manager->make($image)->resize(100, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');

            $result=  $base64 ? $base64 : null;
            // return $this->ttdDokter ? $base64 : null;
            return $result;
        } else {
            return null;
        }
    }
    public function getTtdPetugasUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->ttdPetugas);
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            // $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($image));
            $manager = new ImageManager();
            $base64 = (string) $manager->make($image)->resize(100, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');

            $result=  $base64 ? $base64 : null;
            // return $this->ttdDokter ? $base64 : null;
            return $result;
        } else {
            return null;
        }
    }
    public function getTtdSaksiPasienUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->ttdSaksiPasien);
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            // $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($image));
            $manager = new ImageManager();
            $base64 = (string) $manager->make($image)->resize(100, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');

            $result=  $base64 ? $base64 : null;
            // return $this->ttdDokter ? $base64 : null;
            return $result;
        } else {
            return null;
        }
    }
    public function getTtdYgMenyatakanUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->ttdYgMenyatakan);
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            // $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($image));
            $manager = new ImageManager();
            $base64 = (string) $manager->make($image)->resize(100, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');

            $result=  $base64 ? $base64 : null;
            // return $this->ttdDokter ? $base64 : null;
            return $result;
        } else {
            return null;
        }
    }

    function scopeWithAccessor($query) {
        $query->append('ttd_dokter_url');
    }

}
