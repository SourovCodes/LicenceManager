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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('license_key')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('status')->default('active'); // active, expired, revoked
            $table->integer('validity_days')->default(365); // Duration in days
            $table->timestamp('activated_at')->nullable(); // First activation date
            $table->timestamp('expires_at')->nullable(); // Calculated from activated_at + validity_days
            $table->integer('max_domain_changes')->default(3);
            $table->integer('domain_changes_used')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['license_key', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
