<?php

use App\Modules\User\Enums\UserKYBStatusEnum;
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
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('business_name')->index()->nullable();
            $table->enum('kyb_status', UserKYBStatusEnum::toArray())->index()->nullable();
            $table->string('tin')->index()->nullable();
            $table->string('cac')->nullable();
            $table->string('cloudinary_public_id')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0.00);
            $table->unsignedInteger('approximate_shopping_time')->index()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
