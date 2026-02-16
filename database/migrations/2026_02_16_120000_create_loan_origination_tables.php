<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->string('cif_number')->nullable();
            $table->string('customer_name');
            $table->enum('segment', ['corporate', 'commercial', 'commex']);
            $table->string('loan_type')->default('term_loan');
            $table->string('apk_type')->nullable();
            $table->text('purpose')->nullable();
            $table->decimal('plafond_amount', 18, 2)->default(0);
            $table->integer('tenor_months')->nullable();
            $table->enum('bwmk_type', ['non_deviasi', 'deviasi'])->nullable();
            $table->string('rm_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->enum('current_stage', ['draft', 'review', 'legal', 'acceptance', 'approved', 'rejected', 'disbursed'])->default('draft');
            $table->enum('ideb_result_status', ['pending', 'clear', 'watchlist'])->default('pending');
            $table->timestamp('ideb_requested_at')->nullable();
            $table->decimal('total_collateral_value', 18, 2)->default(0);
            $table->decimal('total_liquidation_value', 18, 2)->default(0);
            $table->date('expected_disbursement_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_notes')->nullable();
            $table->json('business_snapshot')->nullable();
            $table->timestamps();

            $table->index('cif_number');
            $table->index('segment');
            $table->index('current_stage');
            $table->index('bwmk_type');
        });

        Schema::create('loan_collaterals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->enum('collateral_type', ['property', 'non_property']);
            $table->string('collateral_subtype');
            $table->string('description')->nullable();
            $table->decimal('appraisal_value', 18, 2)->default(0);
            $table->decimal('liquidation_value', 18, 2)->default(0);
            $table->string('ownership_name')->nullable();
            $table->string('document_number')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->string('document_name');
            $table->enum('document_category', ['predefined', 'additional'])->default('predefined');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_uploaded')->default(false);
            $table->enum('verification_status', ['pending', 'valid', 'invalid'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_application_id', 'document_name']);
        });

        Schema::create('loan_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->integer('sequence_no');
            $table->string('approver_role');
            $table->string('approver_name')->nullable();
            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('decision_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_application_id', 'sequence_no']);
        });

        Schema::create('loan_covenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->enum('covenant_phase', ['pre_disbursement', 'at_disbursement', 'post_disbursement']);
            $table->text('covenant_text');
            $table->boolean('is_mandatory')->default(true);
            $table->enum('status', ['open', 'fulfilled', 'waived'])->default('open');
            $table->date('due_date')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action');
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_activity_logs');
        Schema::dropIfExists('loan_covenants');
        Schema::dropIfExists('loan_approvals');
        Schema::dropIfExists('loan_documents');
        Schema::dropIfExists('loan_collaterals');
        Schema::dropIfExists('loan_applications');
    }
};
