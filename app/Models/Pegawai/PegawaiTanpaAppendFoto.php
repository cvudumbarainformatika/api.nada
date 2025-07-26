<?php

namespace App\Models\Pegawai;

use App\Models\Pegawai\Akses\Role;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\Jabatan;
use App\Models\Pegawai\JabatanTambahan;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\JenisPegawai;
use App\Models\Pegawai\Ruangan;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Master\Mpoli;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Intervention\Image\ImageManager;

class PegawaiTanpaAppendFoto extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $table = 'pegawai';
    protected $guarded = ['id'];
    // protected $hidden = [];

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
