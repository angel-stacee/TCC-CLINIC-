<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('clinic_visits', 'faculty_id')) {
                $table->foreignId('faculty_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('faculties')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('clinic_visits', 'medicine_quantity')) {
                $table->unsignedInteger('medicine_quantity')
                    ->nullable()
                    ->after('medicine_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_visits', 'faculty_id')) {
                $table->dropForeign(['faculty_id']);
                $table->dropColumn('faculty_id');
            }

            if (Schema::hasColumn('clinic_visits', 'medicine_quantity')) {
                $table->dropColumn('medicine_quantity');
            }
        });
    }
};