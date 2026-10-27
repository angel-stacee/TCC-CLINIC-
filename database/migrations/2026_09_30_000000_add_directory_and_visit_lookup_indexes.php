<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->index('first_name', 'staff_first_name_index');
            $table->index('middle_name', 'staff_middle_name_index');
            $table->index('last_name', 'staff_last_name_index');
            $table->index(['last_name', 'id'], 'staff_last_name_id_index');
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->index('first_name', 'faculties_first_name_index');
            $table->index('middle_name', 'faculties_middle_name_index');
            $table->index('last_name', 'faculties_last_name_index');
            $table->index(['last_name', 'id'], 'faculties_last_name_id_index');
        });

        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->index(['student_id', 'visit_date', 'id'], 'visits_student_date_id_index');
            $table->index(['staff_id', 'visit_date', 'id'], 'visits_staff_date_id_index');
            $table->index(['faculty_id', 'visit_date', 'id'], 'visits_faculty_date_id_index');
            $table->index(['nurse_id', 'visit_date', 'id'], 'visits_nurse_date_id_index');
            $table->index(['medicine_id', 'visit_date', 'id'], 'visits_medicine_date_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('staff_first_name_index');
            $table->dropIndex('staff_middle_name_index');
            $table->dropIndex('staff_last_name_index');
            $table->dropIndex('staff_last_name_id_index');
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->dropIndex('faculties_first_name_index');
            $table->dropIndex('faculties_middle_name_index');
            $table->dropIndex('faculties_last_name_index');
            $table->dropIndex('faculties_last_name_id_index');
        });

        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->dropIndex('visits_student_date_id_index');
            $table->dropIndex('visits_staff_date_id_index');
            $table->dropIndex('visits_faculty_date_id_index');
            $table->dropIndex('visits_nurse_date_id_index');
            $table->dropIndex('visits_medicine_date_id_index');
        });
    }
};
