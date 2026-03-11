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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('clock_out_ip');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->decimal('out_lat', 10, 7)->nullable()->after('lng');
            $table->decimal('out_lng', 10, 7)->nullable()->after('out_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'out_lat', 'out_lng']);
        });
    }
};
