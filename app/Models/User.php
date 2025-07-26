<?php

namespace App\Models;

use App\Models\Pegawai\Akses\AksesUser;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $connection = 'mysql';
    protected $table = 'accounts';
    protected $guarded = ['id'];
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    // public function log($message)
    // {
    //     $message = ucwords($message);
    //     $data = [
    //         'user_id'=>$this->id,
    //         'name'=>$this->name,
    //         'date'=>Carbon::parse(now())->toString(),
    //         'activity'=> $message
    //         // 'activity'=>"{$this->name} $message"
    //     ];
    //    AuditLog::query()->create($data);
    // }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }
    public function jadwal()
    {
        return $this->hasMany(JadwalAbsen::class);
    }
    public function absens()
    {
        return $this->hasMany(TransaksiAbsen::class);
    }
    public function libur()
    {
        return $this->hasMany(Libur::class);
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }

    public function akses()
    {
        return $this->hasMany(AksesUser::class);
    }


    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%');
            // ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
    public function scopeSelectAll($query)
    {
        $query->select('username', 'nama', 'email', 'pegawai_id');
    }
}
