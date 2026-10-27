<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::query()
            ->select(['id', 'name', 'email', 'role', 'profile_picture', 'password'])
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $user->createToken(
            'clinic-system-token'
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()?->currentAccessToken()?->delete();
        } catch (\Throwable $e) {
            // Ignore token deletion error on logout
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($request->has('email')) {
            $request->merge([
                'email' => Str::lower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'avatar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        // Account email is permanent and cannot be changed via profile update

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = 'avatar_'.$user->id.'_'.time().'.'.$extension;
            $destination = public_path('storage/avatars');

            if ($user->profile_picture && ! str_starts_with($user->profile_picture, 'data:')) {
                $oldFile = public_path($user->profile_picture);
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            try {
                if (! is_dir($destination)) {
                    @mkdir($destination, 0755, true);
                }
                $file->move($destination, $fileName);
            } catch (\Throwable $e) {
                // Ignore filesystem failure if disk is restricted
            }

            $localPath = '/storage/avatars/'.$fileName;
            $savedFilePath = $destination.'/'.$fileName;
            $dataUrl = null;

            if (file_exists($savedFilePath) && filesize($savedFilePath) <= 3 * 1024 * 1024) {
                $mime = mime_content_type($savedFilePath) ?: ('image/'.$extension);
                $dataUrl = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($savedFilePath));
            }

            if ($dataUrl) {
                try {
                    $user->profile_picture = $dataUrl;
                    $user->save();
                } catch (\Throwable $e) {
                    $user->profile_picture = $localPath;
                    $user->save();
                }
            } else {
                $user->profile_picture = $localPath;
                $user->save();
            }
        } else {
            $user->save();
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->userPayload($user),
        ]);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function sendResetOtp(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json([
                'message' => 'No account was found with that email address.',
            ], 404);
        }

        // Throttle check: allow resend only after cooldown (15s in local/debug, 60s in production)
        $cooldown = (app()->environment('local') || config('app.debug')) ? 15 : 60;
        $existing = DB::table('password_reset_tokens')->where('email', $email)->first();
        if ($existing && $existing->created_at) {
            $elapsed = (int) Carbon::parse($existing->created_at, 'UTC')->diffInSeconds(now('UTC'));
            if ($elapsed < $cooldown) {
                $secondsRemaining = $cooldown - $elapsed;

                return response()->json([
                    'message' => "An OTP code was already sent recently. Please wait {$secondsRemaining} seconds before requesting a new code.",
                ], 429);
            }
        }

        // Generate 6-digit numeric OTP token
        $otp = $this->generateOtp();

        // Store hashed token and timestamp in password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $fromAddress = config('mail.from.address');
        if (! $fromAddress || $fromAddress === 'null' || $fromAddress === 'hello@example.com') {
            $fromAddress = config('mail.mailers.smtp.username') ?: 'no-reply@tcc.edu.ph';
        }
        $fromName = config('mail.from.name') ?: 'TCC Clinic Management System';

        $htmlBody = '
        <div style="font-family: Arial, sans-serif; max-width: 580px; margin: 0 auto; padding: 24px; border: 1px solid #e8dfd4; border-radius: 12px; background-color: #ffffff;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #8a6f50; margin: 0; font-size: 22px;">TCC Clinic Management System</h2>
                <p style="color: #777777; font-size: 13px; margin-top: 4px;">Password Reset Verification Code</p>
            </div>
            <div style="padding: 24px; background-color: #faf8f5; border-radius: 10px; border: 1px solid #f0eae1; text-align: center;">
                <p style="font-size: 15px; color: #333333; margin-top: 0;">Hello <strong>'.htmlspecialchars($user->name).'</strong>,</p>
                <p style="font-size: 14px; color: #555555; line-height: 1.5;">Your TCC Clinic OTP verification code is:</p>
                <div style="margin: 22px auto; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #8a6f50; background: #ffffff; padding: 14px 28px; display: inline-block; border-radius: 10px; border: 2px dashed #8a6f50;">
                    '.$otp.'
                </div>
                <p style="font-size: 13px; color: #777777; margin-bottom: 0;">This code expires in <strong>15 minutes</strong>.</p>
            </div>
            <p style="font-size: 12px; color: #999999; margin-top: 24px; text-align: center; line-height: 1.4;">
                If you did not request this code, please ignore this email or notify the Clinic Administrator.
            </p>
        </div>';

        try {
            $brevoKey = env('BREVO_API_KEY');
            $resendKey = env('RESEND_API_KEY');

            if ($brevoKey) {
                // Send via Brevo REST API over HTTPS (Port 443) - works seamlessly on Render!
                $apiRes = Http::withHeaders([
                    'api-key' => $brevoKey,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ])->timeout(12)->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => $fromName,
                        'email' => $fromAddress,
                    ],
                    'to' => [
                        [
                            'email' => $user->email,
                            'name' => $user->name,
                        ],
                    ],
                    'subject' => 'Your TCC Clinic OTP Code',
                    'htmlContent' => $htmlBody,
                ]);

                if (! $apiRes->successful()) {
                    throw new \Exception('Brevo API error ('.$apiRes->status().'): '.$apiRes->body());
                }
            } elseif ($resendKey) {
                // Send via Resend REST API over HTTPS (Port 443)
                $apiRes = Http::withHeaders([
                    'Authorization' => 'Bearer '.$resendKey,
                    'content-type' => 'application/json',
                ])->timeout(12)->post('https://api.resend.com/emails', [
                    'from' => "{$fromName} <{$fromAddress}>",
                    'to' => [$user->email],
                    'subject' => 'Your TCC Clinic OTP Code',
                    'html' => $htmlBody,
                ]);

                if (! $apiRes->successful()) {
                    throw new \Exception('Resend API error ('.$apiRes->status().'): '.$apiRes->body());
                }
            } else {
                Mail::html($htmlBody, function ($message) use ($user, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName)
                        ->to($user->email, $user->name)
                        ->subject('Your TCC Clinic OTP Code');
                });
            }

            $isLogMailer = config('mail.default') === 'log' || env('MAIL_MAILER') === 'log';
            $isLocal = app()->environment('local') || config('app.debug');

            return response()->json([
                'message' => $isLogMailer
                    ? "OTP code generated: {$otp} (Mailer is currently set to 'log' in .env)"
                    : "✅ OTP sent to {$user->email}. Please check your inbox.",
                'dev_otp' => ($isLocal || $isLogMailer) ? $otp : null,
            ]);
        } catch (\Throwable $e) {
            $rawError = $e->getMessage();
            Log::error("Mail failed for {$user->email}: ".$rawError);

            // In local development, provide fallback so testing is never blocked by SMTP issues
            if (app()->environment('local') || config('app.debug')) {
                Log::info("Local Dev OTP for {$user->email}: {$otp}");

                return response()->json([
                    'message' => "⚠️ Email sending failed. [Dev Mode OTP: {$otp}]",
                    'dev_otp' => $otp,
                ]);
            }

            $reason = 'Email could not be delivered. Check your mail settings.';

            // Render / cloud host blocked SMTP ports (25, 465, 587)
            if (
                str_contains($rawError, 'Connection timed out') ||
                str_contains($rawError, 'Connection refused') ||
                str_contains($rawError, 'Operation timed out') ||
                str_contains($rawError, 'Network is unreachable')
            ) {
                $reason = 'Could not connect to SMTP server. Cloud hosts like Render block SMTP ports 25, 465, and 587 on free tiers. Add BREVO_API_KEY in your Render environment variables to send emails via HTTPS.';
            }
            // Brevo / generic SMTP auth failures
            elseif (
                str_contains($rawError, '535') ||
                str_contains($rawError, 'BadCredentials') ||
                str_contains($rawError, 'Username and Password not accepted') ||
                str_contains($rawError, 'Authentication Required') ||
                str_contains($rawError, '530')
            ) {
                $reason = 'SMTP authentication failed. Verify your Gmail App Password or Brevo credentials in your server environment variables.';
            }

            return response()->json([
                'message' => "Unable to send OTP: {$reason}",
            ], 500);
        }
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function verifyResetOtp(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'otp' => trim((string) $request->input('otp')),
        ]);

        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = $request->input('email');
        $otp = $request->input('otp');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return response()->json([
                'message' => 'No OTP request was found for this email. Please request a new code.',
            ], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json([
                'message' => 'The OTP code has expired. Please request a new code.',
            ], 400);
        }

        if (! Hash::check($otp, $record->token)) {
            return response()->json([
                'message' => 'Invalid OTP code. Please check your email and try again.',
            ], 400);
        }

        return response()->json([
            'message' => 'OTP verified successfully.',
            'valid' => true,
        ]);
    }

    public function resetPasswordWithOtp(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'otp' => trim((string) $request->input('otp')),
        ]);

        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = $request->input('email');
        $otp = $request->input('otp');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return response()->json([
                'message' => 'No OTP request was found for this email. Please request a new code.',
            ], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json([
                'message' => 'The OTP code has expired. Please request a new code.',
            ], 400);
        }

        if (! Hash::check($otp, $record->token)) {
            return response()->json([
                'message' => 'Invalid OTP code. Please check your email and try again.',
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Revoke active sessions / sanctum tokens for security
        $user->tokens()->delete();

        // Remove token from password_reset_tokens
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
        ]);
    }

    private function userPayload(User $user): array
    {
        return $user->only([
            'id',
            'name',
            'email',
            'role',
            'profile_picture',
        ]);
    }
}
