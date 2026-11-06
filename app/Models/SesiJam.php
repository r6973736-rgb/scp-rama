<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiJam extends Model
{
    protected $table = 'sesi_jam';
    protected $primaryKey = 'id_sesi';
    public $timestamps = false;
    protected $guarded = [];
}
