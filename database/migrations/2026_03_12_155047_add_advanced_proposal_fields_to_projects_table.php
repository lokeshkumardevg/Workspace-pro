<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('target_audience')->nullable();
            $table->text('security_measures')->nullable();
            $table->text('project_scope')->nullable();
            $table->text('milestones_content')->nullable();
            $table->text('deliverables')->nullable();
            $table->text('maintenance_support')->nullable();
            $table->text('terms_conditions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'target_audience',
                'security_measures',
                'project_scope',
                'milestones_content',
                'deliverables',
                'maintenance_support',
                'terms_conditions'
            ]);
        });
    }
};
