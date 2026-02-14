<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dashboard_import_payloads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id')->index();
            $table->longText('mapping_json');
            $table->longText('rows_json');
            $table->timestamps();

            $table->foreign('batch_id')
                ->references('id')
                ->on('dashboard_import_batches')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_import_payloads');
    }
};
