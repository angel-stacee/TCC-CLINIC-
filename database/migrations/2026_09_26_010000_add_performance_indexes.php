<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('first_name', 'students_first_name_index');
            $table->index('middle_name', 'students_middle_name_index');
            $table->index('last_name', 'students_last_name_index');
            $table->index('course', 'students_course_index');
            $table->index('year_level', 'students_year_level_index');
            $table->index('section', 'students_section_index');
            $table->index('sex', 'students_sex_index');
            $table->index(['created_at', 'id'], 'students_created_at_id_index');
        });

        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->index(['visit_date', 'id'], 'clinic_visits_visit_date_id_index');
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->index('medicine_name', 'medicines_medicine_name_index');
            $table->index('treatment_type', 'medicines_treatment_type_index');
            $table->index('unit', 'medicines_unit_index');
            $table->index(['created_at', 'id'], 'medicines_created_at_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_first_name_index');
            $table->dropIndex('students_middle_name_index');
            $table->dropIndex('students_last_name_index');
            $table->dropIndex('students_course_index');
            $table->dropIndex('students_year_level_index');
            $table->dropIndex('students_section_index');
            $table->dropIndex('students_sex_index');
            $table->dropIndex('students_created_at_id_index');
        });

        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->dropIndex('clinic_visits_visit_date_id_index');
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->dropIndex('medicines_medicine_name_index');
            $table->dropIndex('medicines_treatment_type_index');
            $table->dropIndex('medicines_unit_index');
            $table->dropIndex('medicines_created_at_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });
    }
};
