<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_can_request_otp_and_reset_password(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'nurse@tcc.edu.ph',
            'password' => Hash::make('OldPassword123!'),
        ]);

        // 1. Request OTP
        $response = $this->postJson('/api/forgot-password/send-otp', [
            'email' => 'nurse@tcc.edu.ph',
        ]);

        $response->assertStatus(200);

        // Check password_reset_tokens table has entry
        $resetRecord = DB::table('password_reset_tokens')->where('email', 'nurse@tcc.edu.ph')->first();
        $this->assertNotNull($resetRecord);

        // 2. Reject incorrect OTP
        $verifyFail = $this->postJson('/api/forgot-password/verify-otp', [
            'email' => 'nurse@tcc.edu.ph',
            'otp' => '000000',
        ]);
        $verifyFail->assertStatus(400);

        // 3. Reset password using a known OTP
        DB::table('password_reset_tokens')->where('email', 'nurse@tcc.edu.ph')->update([
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $verifyPass = $this->postJson('/api/forgot-password/verify-otp', [
            'email' => 'nurse@tcc.edu.ph',
            'otp' => '123456',
        ]);
        $verifyPass->assertStatus(200);

        $resetResponse = $this->postJson('/api/forgot-password/reset', [
            'email' => 'nurse@tcc.edu.ph',
            'otp' => '123456',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $resetResponse->assertStatus(200);

        // Token should be deleted from table
        $this->assertNull(DB::table('password_reset_tokens')->where('email', 'nurse@tcc.edu.ph')->first());

        // User should now be able to log in with new password
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'nurse@tcc.edu.ph',
            'password' => 'NewSecurePassword123!',
        ]);

        $loginResponse->assertStatus(200);
        $this->assertArrayHasKey('token', $loginResponse->json());
    }

    public function test_send_otp_fails_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/forgot-password/send-otp', [
            'email' => 'unknown@tcc.edu.ph',
        ]);

        $response->assertStatus(404);
    }
}

