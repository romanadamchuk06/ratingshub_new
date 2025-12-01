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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Basic, Pro, Enterprise
            $table->string('slug')->unique(); // free, basic, pro, enterprise
            $table->string('stripe_plan_id')->nullable(); // Stripe Price ID
            $table->decimal('price', 10, 2)->default(0); // Monthly price
            $table->integer('max_platforms')->default(1); // Max connected platforms
            $table->boolean('analytics')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('white_label')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->json('features')->nullable(); // JSON array of features
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
