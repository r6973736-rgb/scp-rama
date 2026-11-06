<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';
    protected $guarded = [];
    public $timestamps = false;

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'student_id', 'id_siswa');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
