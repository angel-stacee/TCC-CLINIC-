<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('clinic_visits', 'is_emergency')) {
                $table->boolean('is_emergency')->default(false)->after('contact_number');
            }

            if (!Schema::hasColumn('clinic_visits', 'sms_sent_at')) {
                $table->timestamp('sms_sent_at')->nullable()->after('is_emergency');
            }

            if (!Schema::hasColumn('clinic_visits', 'sms_status')) {
                $table->string('sms_status', 50)->nullable()->after('sms_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_visits', 'sms_status')) {
                $table->dropColumn('sms_status');
            }

            if (Schema::hasColumn('clinic_visits', 'sms_sent_at')) {
                $table->dropColumn('sms_sent_at');
            }

            if (Schema::hasColumn('clinic_visits', 'is_emergency')) {
                $table->dropColumn('is_emergency');
            }
        });
    }
};

