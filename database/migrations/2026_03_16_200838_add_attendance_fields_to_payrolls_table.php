<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'present_days')) {
                $table->integer('present_days')->default(0)->after('status');
            }
            if (!Schema::hasColumn('payrolls', 'working_days')) {
                $table->integer('working_days')->default(0)->after('present_days');
            }
            if (!Schema::hasColumn('payrolls', 'lop_days')) {
                $table->integer('lop_days')->default(0)->after('working_days');
            }
            if (!Schema::hasColumn('payrolls', 'pf_contribution')) {
                $table->decimal('pf_contribution', 10, 2)->default(0)->after('lop_days');
            }
            if (!Schema::hasColumn('payrolls', 'professional_tax')) {
                $table->decimal('professional_tax', 10, 2)->default(0)->after('pf_contribution');
            }
            if (!Schema::hasColumn('payrolls', 'lop_deduction')) {
                $table->decimal('lop_deduction', 10, 2)->default(0)->after('professional_tax');
            }
            if (!Schema::hasColumn('payrolls', 'extra_deductions')) {
                $table->decimal('extra_deductions', 10, 2)->default(0)->after('lop_deduction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['present_days', 'working_days', 'lop_days', 'pf_contribution', 'professional_tax', 'lop_deduction', 'extra_deductions']);
        });
    }
};
