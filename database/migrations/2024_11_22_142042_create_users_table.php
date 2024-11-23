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
        // تعديل جدول المستخدمين
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');  // الاسم الأول
            $table->string('last_name');   // الاسم الأخير
            $table->string('phone_number')->unique();  // رقم الهاتف بدلاً من البريد الإلكتروني
            $table->timestamp('phone_verified_at')->nullable();  // تاريخ التحقق من رقم الهاتف
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // جدول إعادة تعيين كلمات المرور (إذا كان بحاجة لتعديل)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone_number')->primary();  // رقم الهاتف بدلاً من البريد الإلكتروني
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // جدول الجلسات (إذا كان بحاجة لتعديل)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
