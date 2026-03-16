<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'from_date',
        'to_date',
        'days',
        'reason',
        'status',
        'is_paid',
        'reviewed_by',
        'review_note'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_paid' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
