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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('study_class_id')
                ->nullable()
                ->constrained('study_classes')
                ->nullOnDelete();

            $table->string('student_number')->nullable();
            $table->string('tpq_number')->nullable();

            $table->string('name');

            $table->string('nisn')->nullable()->unique();
            $table->string('nik')->nullable();
            $table->string('family_card_number')->nullable();

            $table->enum('gender', ['male', 'female']);

            $table->string('birth_place')->nullable();
            $table->date('birth_date');

            $table->date('join_date')->nullable();

            $table->unsignedTinyInteger('child_order')->nullable();
            $table->unsignedTinyInteger('siblings_count')->nullable();

            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('contact_guardian')->nullable();

            $table->string('hamlet')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();

            $table->string('formal_school')->nullable();
            $table->string('formal_class')->nullable();
            $table->string('npsn')->nullable();

            $table->enum('student_type', ['regular', 'pre_qiraati', 'qiraati'])->default('regular');

            $table->enum('status', ['pending', 'active', 'graduated', 'left'])->default('pending');

            $table->string('photo')->nullable();
            $table->string('family_card_file')->nullable();
            $table->string('birth_certificate_file')->nullable();

            $table->boolean('age_notification_sent')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
