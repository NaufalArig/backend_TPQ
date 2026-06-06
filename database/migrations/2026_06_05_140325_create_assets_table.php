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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_category_id')
                ->nullable()
                ->constrained('asset_categories')
                ->nullOnDelete();

            $table->string('asset_code')->nullable()->unique();
            $table->string('name');
            $table->string('brand')->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit')->nullable();

            $table->date('acquisition_date')->nullable();
            $table->string('source')->nullable();
            $table->string('location')->nullable();

            $table->enum('condition', ['good', 'minor_damage', 'damaged', 'lost'])->default('good');
            $table->enum('status', ['available', 'in_use', 'maintenance', 'disposed'])->default('available');

            $table->decimal('estimated_value', 15, 2)->nullable();

            $table->string('photo')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
