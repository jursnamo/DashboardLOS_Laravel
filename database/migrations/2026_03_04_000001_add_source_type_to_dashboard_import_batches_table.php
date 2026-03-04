<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_import_batches', function (Blueprint $table) {
            $table->string('source_type', 32)->default('upload_file')->after('filename');
            $table->index(['source_type', 'status'], 'dib_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_import_batches', function (Blueprint $table) {
            $table->dropIndex('dib_source_status_idx');
            $table->dropColumn('source_type');
        });
    }
};
