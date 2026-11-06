<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiHeader extends Model
{
    protected $table      = 'presensi_header';
    protected $primaryKey = 'id_presensi';
    public $timestamps    = false;
    protected $guarded    = [];

    /** Jadwal pelajaran yang dicatat presensinya */
    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'id_jadwal', 'id_jadwal');
    }

    /** Ruangan yang digunakan saat KBM */
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_digunakan', 'id_ruangan');
    }

    /** Guru yang mengajar */
    public function guruPengajar()
    {
        return $this->belongsTo(Guru::class, 'id_guru_pengajar', 'id_guru');
    }

    /** Detail kehadiran tiap siswa */
    public function detail()
    {
        return $this->hasMany(PresensiDetail::class, 'id_presensi', 'id_presensi');
    }
}

