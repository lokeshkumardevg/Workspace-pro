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
            $table->string('client_name')->nullable()->after('name');
            $table->decimal('budget', 15, 2)->default(0)->after('description');
            $table->text('technology_stack')->nullable()->after('budget');
            $table->integer('estimated_hours')->default(0)->after('technology_stack');
            $table->integer('team_size')->default(1)->after('estimated_hours');
            $table->longText('proposal_content')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['client_name', 'budget', 'technology_stack', 'estimated_hours', 'team_size', 'proposal_content']);
        });
    }
};
