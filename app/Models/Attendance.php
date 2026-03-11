<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_ip',
        'clock_out_ip',
        'lat',
        'lng',
        'out_lat',
        'out_lng',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
