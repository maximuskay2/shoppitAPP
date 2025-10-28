<?php

use App\Modules\User\Enums\UserKYCStatusEnum;
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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index()->nullable();
            $table->string('username')->index()->unique()->nullable();
            $table->uuid('referred_by_user_id')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique()->nullable();
            $table->string('country')->default('Nigeria');
            $table->string('state')->index()->nullable();
            $table->string('city')->index()->nullable();
            $table->text('address')->nullable();
            $table->text('address_2')->nullable();
            $table->string('referral_code')->index()->unique()->nullable();
            $table->string('avatar')->nullable();
            $table->enum('kyc_status', UserKYCStatusEnum::toArray())->index()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('push_in_app_notifications')->default(true);
            $table->timestamp('last_logged_in_at')->nullable();
            $table->string('last_logged_in_device')->index()->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('referred_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('users_referred_by_user_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
