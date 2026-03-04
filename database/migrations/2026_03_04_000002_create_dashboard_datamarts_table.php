<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_datamarts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('dashboard_import_batches')->cascadeOnDelete();
            $table->unsignedBigInteger('source_batch_id');
            $table->string('status', 32)->default('completed');
            $table->unsignedInteger('records_count')->default(0);
            $table->longText('payload_json');
            $table->timestamps();

            $table->index(['status', 'created_at'], 'ddm_status_created_idx');
            $table->index('source_batch_id', 'ddm_source_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_datamarts');
    }
};
