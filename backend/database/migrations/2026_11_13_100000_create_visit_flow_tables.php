<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEMPORARY, like the walking skeleton migration: the visit flow and queue
 * tables, with columns copied from docs/data-model.md. Delete this file when
 * the real schema migration lands.
 *
 * Left out until the insurance slice exists: visitation.patient_insurance_id,
 * insurance_verified_by and insurance_verified_at (patient_insurance has no
 * table yet, so there is nothing to point the foreign key at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoint', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->smallInteger('sequence')->unique();
        });

        Schema::create('visitation', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number')->unique();
            $table->foreignId('patient_id')->constrained('patient');
            $table->text('visit_type');
            $table->text('priority');
            $table->boolean('patient_consent');
            $table->foreignId('current_checkpoint_id')->nullable()->constrained('checkpoint');
            $table->text('status');
            $table->timestampTz('arrived_at')->useCurrent();
            $table->foreignId('registered_by')->constrained('app_user');
            $table->timestampTz('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('app_user');
            $table->text('closure_reason')->nullable();

            $table->index(['status', 'current_checkpoint_id']);
        });

        Schema::create('visitation_checkpoint_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitation_id')->constrained('visitation');
            $table->foreignId('checkpoint_id')->constrained('checkpoint');
            $table->timestampTz('entered_at')->useCurrent();
            $table->timestampTz('cleared_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('app_user');
            $table->boolean('skipped')->default(false);
            $table->text('note')->nullable();
        });

        Schema::create('visitation_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitation_id')->constrained('visitation');
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->smallInteger('bp_systolic')->nullable();
            $table->smallInteger('bp_diastolic')->nullable();
            $table->smallInteger('pulse_bpm')->nullable();
            $table->smallInteger('resp_rate')->nullable();
            $table->smallInteger('spo2_pct')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('muac_cm', 4, 1)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('app_user');
            $table->timestampTz('recorded_at')->useCurrent();
        });
    }

    public function down(): void {} // forward-only
};
