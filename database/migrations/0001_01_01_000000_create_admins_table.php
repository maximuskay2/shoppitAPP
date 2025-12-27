<?php

use App\Modules\User\Enums\KYCLevelEnum;
use App\Modules\User\Enums\KYCStatusEnum;
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
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index()->nullable();
            $table->foreignUuid('role_id')->nullable()->references('id')->on('roles')->onDelete('set null');
            $table->string('email')->index()->unique();
            $table->string('password');
            $table->string('avatar')->nullable(); 
            $table->json('permissions')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
