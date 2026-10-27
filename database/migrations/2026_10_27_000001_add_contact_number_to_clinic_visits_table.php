<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('clinic_visits', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('nurse_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_visits', 'contact_number')) {
                $table->dropColumn('contact_number');
            }
        });
    }
};

