<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('clinic_visits', 'staff_id')) {
                $table->foreignId('staff_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('staff')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('clinic_visits', 'symptoms')) {
                $table->text('symptoms')->nullable();
            }

            if (!Schema::hasColumn('clinic_visits', 'temperature')) {
                $table->string('temperature', 50)->nullable();
            }

            if (!Schema::hasColumn('clinic_visits', 'blood_pressure')) {
                $table->string('blood_pressure', 50)->nullable();
            }

            if (!Schema::hasColumn('clinic_visits', 'assessment')) {
                $table->text('assessment')->nullable();
            }

            if (!Schema::hasColumn('clinic_visits', 'treatment')) {
                $table->text('treatment')->nullable();
            }

            if (!Schema::hasColumn('clinic_visits', 'remarks')) {
                $table->text('remarks')->nullable();
            }

            if (!Schema::hasColumn('clinic_visits', 'medicine_id')) {
                $table->foreignId('medicine_id')
                    ->nullable()
                    ->constrained('medicines')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_visits', 'staff_id')) {
                $table->dropForeign(['staff_id']);
                $table->dropColumn('staff_id');
            }

            if (Schema::hasColumn('clinic_visits', 'medicine_id')) {
                $table->dropForeign(['medicine_id']);
                $table->dropColumn('medicine_id');
            }

            $columns = [
                'symptoms',
                'temperature',
                'blood_pressure',
                'assessment',
                'treatment',
                'remarks',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('clinic_visits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};