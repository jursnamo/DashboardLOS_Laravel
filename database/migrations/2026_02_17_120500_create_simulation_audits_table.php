<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_audits', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('detected_status', 120);
            $table->unsignedSmallInteger('requested_value');
            $table->unsignedSmallInteger('applied_value');
            $table->boolean('was_clamped')->default(false);
            $table->string('provider', 24)->default('cloudflare');
            $table->longText('simulator_payload');
            $table->text('system_summary');
            $table->text('ai_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['detected_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_audits');
    }
};
