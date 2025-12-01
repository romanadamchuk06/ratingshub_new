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
        Schema::table('plans', function (Blueprint $table) {
            // Remove unnecessary feature flags
            $table->dropColumn([
                'analytics',
                'priority_support',
                'api_access',
                'white_label',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('analytics')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('white_label')->default(false);
        });
    }
};
