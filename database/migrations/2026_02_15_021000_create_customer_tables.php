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
        // 1. Main Table: Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->string('cif_number')->primary(); // Primary Key (String)
            $table->string('legal_name');
            $table->string('previous_name')->nullable();
            $table->string('constitution')->nullable();
            $table->date('incorporation_date')->nullable();
            $table->integer('employee_count')->nullable();
            $table->boolean('is_related_party')->default(false);
            $table->string('slik_purpose_code')->nullable();
            $table->string('finance_risk_level')->nullable();
            $table->string('account_strategy')->nullable();
            $table->timestamps();
        });

        // 2. Customer IDs
        Schema::create('customer_ids', function (Blueprint $table) {
            $table->id();
            $table->string('cif_number');
            $table->string('id_type')->comment('KTP/NPWP/Passport');
            $table->string('id_number');
            $table->string('issuance_place')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();

            $table->foreign('cif_number')->references('cif_number')->on('customers')->onDelete('cascade');
        });

        // 3. Industry Info
        Schema::create('industry_infos', function (Blueprint $table) {
            $table->id();
            $table->string('cif_number');
            $table->string('segment')->nullable();
            $table->string('cimb_sectoral_code')->nullable();
            $table->string('bi_classification')->nullable();
            $table->string('basel_obligor_type')->nullable();
            $table->string('sektor_ekonomi')->nullable();
            $table->string('golongan_debitur')->nullable();
            $table->timestamps();

            $table->foreign('cif_number')->references('cif_number')->on('customers')->onDelete('cascade');
        });

        // 4. Addresses
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('cif_number');
            $table->string('address_type')->nullable();
            $table->string('format')->nullable();
            $table->string('line_1')->nullable();
            $table->string('line_2')->nullable();
            $table->string('line_3')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();

            $table->foreign('cif_number')->references('cif_number')->on('customers')->onDelete('cascade');
        });

        // 5. Internal Reference
        Schema::create('internal_references', function (Blueprint $table) {
            $table->id();
            $table->string('cif_number');
            $table->string('rm_code')->nullable();
            $table->string('rm_name')->nullable();
            $table->string('bct_name')->nullable();
            $table->string('originating_unit')->nullable();
            $table->string('controlling_branch')->nullable();
            $table->timestamps();

            $table->foreign('cif_number')->references('cif_number')->on('customers')->onDelete('cascade');
        });

        // 6. Contact Person
        Schema::create('contact_people', function (Blueprint $table) {
            $table->id();
            $table->string('cif_number');
            $table->string('name');
            $table->string('contact_type')->nullable();
            $table->string('info')->nullable();
            $table->timestamps();

            $table->foreign('cif_number')->references('cif_number')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_people');
        Schema::dropIfExists('internal_references');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('industry_infos');
        Schema::dropIfExists('customer_ids');
        Schema::dropIfExists('customers');
    }
};
