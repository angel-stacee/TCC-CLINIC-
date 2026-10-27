<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // Default password: password123
        $defaultPasswordHash = Hash::make('password123');

        $users = [
            [
                'name' => 'Angel',
                'email' => 'admin.clinic.edu@gmail.com',
                'role' => 'Nurse',
                'password' => $defaultPasswordHash,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ralph',
                'email' => 'tcc_clinic@tcc.edu.ph',
                'role' => 'Nurse',
                'password' => $defaultPasswordHash,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $existing = DB::table('users')->where('email', $userData['email'])->first();

            if (! $existing) {
                DB::table('users')->insert($userData);
            } else {
                DB::table('users')->where('email', $userData['email'])->update([
                    'password' => $defaultPasswordHash,
                    'role' => 'Nurse',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep user records safe on rollback
    }
};

