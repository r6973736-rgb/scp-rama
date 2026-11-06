<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Kelas extends Model {
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Nama kelas untuk tampilan, contoh: "X RPL 1" (tingkat + jurusan).
     * Kalau keduanya kosong, dipakai id_kelas.
     */
    public function getNamaKelasAttribute()
    {
        $nama = trim(($this->attributes['tingkat'] ?? '') . ' ' . ($this->attributes['id_jurusan'] ?? ''));

        return $nama !== '' ? $nama : (string) ($this->attributes['id_kelas'] ?? '');
    }

    /** Siswa yang terdaftar di kelas ini */
    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }

    /** Guru wali kelas (kolom kelas.id_wali_kelas) */
    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'id_wali_kelas', 'id_guru');
    }
}
