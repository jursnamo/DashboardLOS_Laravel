<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_approval_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('division');
            $table->enum('segment', ['corporate', 'commercial', 'commex']);
            $table->enum('bwmk_type', ['non_deviasi', 'deviasi'])->nullable();
            $table->enum('actor_type', ['maker', 'checker', 'approver'])->default('approver');
            $table->unsignedInteger('sequence_no');
            $table->string('role_name');
            $table->unsignedInteger('sla_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['division', 'segment', 'bwmk_type', 'is_active'], 'lam_filter_idx');
            $table->unique(
                ['division', 'segment', 'bwmk_type', 'actor_type', 'sequence_no', 'role_name'],
                'lam_unique_idx'
            );
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->string('division')->default('Commercial')->after('customer_name');
        });

        Schema::table('loan_approvals', function (Blueprint $table) {
            $table->enum('actor_type', ['maker', 'checker', 'approver'])->default('approver')->after('sequence_no');
            $table->unsignedInteger('sla_hours')->nullable()->after('notes');
            $table->foreignId('assigned_user_id')->nullable()->after('sla_hours')->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable()->after('assigned_user_id');
            $table->timestamp('due_at')->nullable()->after('started_at');
            $table->timestamp('completed_at')->nullable()->after('due_at');
        });

        Schema::table('loan_documents', function (Blueprint $table) {
            $table->string('file_disk')->nullable()->after('notes');
            $table->string('file_path')->nullable()->after('file_disk');
            $table->string('original_filename')->nullable()->after('file_path');
            $table->string('file_mime', 120)->nullable()->after('original_filename');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
        });
    }

    public function down(): void
    {
        Schema::table('loan_documents', function (Blueprint $table) {
            $table->dropColumn(['file_disk', 'file_path', 'original_filename', 'file_mime', 'file_size']);
        });

        Schema::table('loan_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn(['actor_type', 'sla_hours', 'started_at', 'due_at', 'completed_at']);
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn('division');
        });

        Schema::dropIfExists('loan_approval_matrices');
    }
};
