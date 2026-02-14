<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('dashboard_import_batches')->cascadeOnDelete();
            $table->string('app_id');
            $table->string('segment')->nullable();
            $table->string('purpose')->nullable();
            $table->decimal('approved_limit', 18, 2)->nullable();
            $table->string('branch_name')->nullable();
            $table->string('booking_month', 7)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->dateTime('complete_date')->nullable();
            $table->decimal('tat_days', 10, 2)->nullable();
            $table->string('status_flow');
            $table->unsignedInteger('row_order')->default(0);
            $table->timestamps();

            $table->index(['batch_id', 'app_id']);
            $table->index(['batch_id', 'status_flow']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_records');
    }
};
