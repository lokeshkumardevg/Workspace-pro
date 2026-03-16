<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
            $table->index('assigned_to');
            $table->index('project_id');
            $table->index('due_date');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['user_id', 'date']);
            $table->index('status');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index('status');
            $table->index('assigned_to');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['status']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assigned_to']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });
    }
};
