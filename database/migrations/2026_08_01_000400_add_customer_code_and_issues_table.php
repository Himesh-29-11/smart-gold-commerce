<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('customer_code')->nullable()->unique()->after('id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('order_code')->nullable()->unique()->after('id');
        });

        Schema::create('customer_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('customer_code')->nullable()->index();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('category', 60)->default('general');
            $table->string('subject', 190);
            $table->text('message');
            $table->string('status', 40)->default('open')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_issues');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('order_code');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('customer_code');
        });
    }
};
