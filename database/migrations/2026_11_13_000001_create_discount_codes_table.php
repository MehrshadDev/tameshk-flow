<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title', 150);
            $table->string('type', 20)->default('percent'); // percent, fixed
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('max_discount_amount')->nullable();
            $table->unsignedBigInteger('min_order_amount')->nullable();
            $table->json('applicable_plan_slugs')->nullable();
            $table->json('applicable_cycles')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedSmallInteger('max_uses_per_user')->nullable()->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('first_purchase_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['code', 'is_active']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('discount_code_id')
                ->nullable()
                ->after('subscription_id')
                ->constrained('discount_codes')
                ->nullOnDelete();

            $table->unsignedBigInteger('discount_amount')
                ->default(0)
                ->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_code_id');
            $table->dropColumn('discount_amount');
        });

        Schema::dropIfExists('discount_codes');
    }
};
