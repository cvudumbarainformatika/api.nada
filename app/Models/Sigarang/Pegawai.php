<?php

namespace App\Models\Sigarang;

use App\Models\Arsip\Master\Munitpengelolah;
use App\Models\Pegawai\Akses\Role;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\Jabatan;
use App\Models\Pegawai\JabatanTambahan;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\JenisPegawai;
use App\Models\Pegawai\Ruangan;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Siasik\TransaksiLS\NpdLS_heder;
use App\Models\Simrs\Master\Mpoli;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Intervention\Image\ImageManager;

class Pegawai extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $table = 'pegawai';
    protected $guarded = ['id'];
    protected $appends = ['ttdpegawai_url', 'foto_pegawai'];
    // protected $hidden = ['foto_pegawai'];

    public $timestamps = false;

    public function relasi_jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan', 'kode_jabatan');
    }
    public function jenis_pegawai()
    {
        return $this->belongsTo(JenisPegawai::class, 'flag', 'kode_jenis');
    }
    public function jabatanTambahan()
    {
        return $this->belongsTo(JabatanTambahan::class, 'jabatan_tmb', 'kode_jabatan');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function jadwal()
    {
        return $this->hasMany(JadwalAbsen::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruang', 'koderuangan');
    }
    public function poli()
    {
        return $this->belongsTo(Mpoli::class, 'kdruangansim', 'rs1');
    }

    public function ruang()
    {
        return $this->hasOne(Ruang::class, 'kode', 'kode_ruang');
    }
    public function ruangsim()
    {
        return $this->hasOne(Ruang::class, 'kode', 'kdruangansim');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function depo()
    {
        return $this->hasOne(Gudang::class, 'kode', 'kode_ruang');
    }
    public function depoSim() // kode gudang menggunakan kolom kdrungansim
    {
        return $this->hasOne(Gudang::class, 'kode', 'kdruangansim');
    }

    public function mapingPengguna()
    {
        return $this->hasOne(PenggunaRuang::class, 'kode_ruang', 'kode_ruang');
    }

    public function transaksi_absen()
    {
        return $this->hasMany(TransaksiAbsen::class);
    }

    public function alpha()
    {
        return $this->hasMany(Alpha::class);
    }

    public function npd_heder()
    {
        return $this->hasMany(NpdLS_heder::class, 'nip', 'nip');
    }

    public function unitpengelolaharsip()
    {
        return $this->hasOne(Munitpengelolah::class, 'kode', 'kdarteri');
    }

    public function getTtdpegawaiUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->ttdpegawai);
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            $base64 = 'data:image/jpg;base64,' . base64_encode(file_get_contents($image));
            return $this->ttdpegawai ? $base64 : null;
        } else {
            return null;
        }
    }
    public function getFotoPegawaiAttribute()
    {
        // $image = "http://192.168.100.100/simpeg/foto/{$this->nip}/{$this->foto}";
        // if (!$image) {
        //     return null;
        // }
        // $handle = @fopen($image, 'r');
        // if ($handle) {
        //     $base64 = 'data:image/jpg;base64,' . base64_encode(file_get_contents($image));
        //     return  $base64 ? $base64 : null;
        // } else {
        //     return null;
        // }
        // $nip = '3574041305820002';
        // $foto = 'foto-3574041305820002.jpg';

        $nip = $this->nip;
        $foto = $this->foto;

        if ($nip === null || $nip === '' || $foto===null || $foto === '') {
            return null;
        }

        $image = "http://192.168.100.100/simpeg/foto/{$this->nip}/{$this->foto}";
        // $image = "http://192.168.100.100/simpeg/foto/{$this->nip}/{$this->foto}";
        // $exist = file_exists($image);
        // if (!$image || !$exist) {
        //     return null;
        // }
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            // $manager = new ImageManager(['driver' => 'imagick']);
            $manager = new ImageManager();
            $base64 = (string) $manager->make($image)->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');

            // $base64 = "data:image/{$extension};base64," . base64_encode(file_get_contents($img));
            $result=  $base64 ? $base64 : null;
            return $result;
        } else {
            return null;
        }

    }




    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nip', 'LIKE', '%' . $query . '%')
                ->orWhere('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('kdpegsimrs', 'LIKE', '%' . $query . '%');
            // ->orWhere('kodemapingrs', 'LIKE', '%' . $query . '%');
        });
    }
}
