<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEMPORARY: the tables the Sprint 0 walking skeleton needs, with columns
 * copied from docs/data-model.md. Delete this file when the real
 * init_anc_schema migration (backend/database/sql/) lands, since that
 * creates the same tables with their triggers and constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->text('sex')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id')->nullable()->unique();
            $table->text('employment_type');
            $table->text('employment_status');
            $table->date('hire_date')->nullable();
            $table->timestampsTz();
        });

        Schema::create('app_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->unique()->constrained('staff');
            $table->string('username')->unique();
            $table->text('password_hash');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('role', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
        });

        Schema::create('permission', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('role');
            $table->foreignId('permission_id')->constrained('permission');
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('app_user');
            $table->foreignId('role_id')->constrained('role');
            $table->timestampTz('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('app_user');
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('patient', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->text('sex');
            $table->date('date_of_birth')->nullable();
            $table->boolean('dob_estimated')->default(false);
            $table->string('national_id')->nullable()->unique();
            $table->text('address')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('app_user');
        });
    }

    public function down(): void {} // forward-only
};
