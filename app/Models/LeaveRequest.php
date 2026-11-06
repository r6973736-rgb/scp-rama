<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leave_requests';

    protected $fillable = [
        'student_id',
        'applicant_id',
        'start_date',
        'end_date',
        'reason',
        'document_path',
        'status_approval',
        'verified_by',
        'rejection_reason',
    ];

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id', 'id_siswa');
    }
}