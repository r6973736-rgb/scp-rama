<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $table = 'system_configurations';
    protected $guarded = [];
    public $timestamps = false;
}