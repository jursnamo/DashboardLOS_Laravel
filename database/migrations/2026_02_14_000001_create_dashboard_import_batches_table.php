<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->string('calculation_mode', 16);
            $table->unsignedInteger('total_rows')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_import_batches');
    }
};
