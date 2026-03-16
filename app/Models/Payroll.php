<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'user_id', 'month', 'year',
        'base_salary', 'deductions', 'bonuses', 'net_salary', 'status',
        'present_days', 'working_days', 'lop_days',
        'pf_contribution', 'professional_tax', 'lop_deduction', 'extra_deductions',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
