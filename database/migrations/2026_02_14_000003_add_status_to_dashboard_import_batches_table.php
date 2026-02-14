<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dashboard_import_batches', function (Blueprint $table) {
            $table->string('status', 32)->default('completed')->after('calculation_mode');
            $table->unsignedInteger('imported_rows')->default(0)->after('total_rows');
            $table->text('error_message')->nullable()->after('imported_rows');
            $table->timestamp('started_at')->nullable()->after('error_message');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_import_batches', function (Blueprint $table) {
            $table->dropColumn(['status', 'imported_rows', 'error_message', 'started_at', 'completed_at']);
        });
    }
};
