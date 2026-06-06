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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('teacher_number')->nullable();
            $table->string('tpq_number')->nullable();

            $table->string('name');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();

            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();

            $table->string('phone')->nullable();

            $table->string('certificate_from')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('education')->nullable();

            $table->date('join_date');
            $table->date('leave_date')->nullable();

            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->string('photo')->nullable();
            $table->boolean('age_notification_sent')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
