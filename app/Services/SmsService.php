<?php

namespace App\Services;

use App\Models\ClinicVisit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmsService
{
    /**
     * Send an SMS notification for a clinic visit.
     */
    public function sendVisitNotification(ClinicVisit $visit, bool $isEmergency = false, ?string $recipientNumber = null): array
    {
        $number = $recipientNumber ?: $visit->contact_number;

        if (empty($number)) {
            return [
                'success' => false,
                'provider' => 'none',
                'message' => 'No cellphone number provided for this clinic visit.',
            ];
        }

        $message = self::formatVisitMessage($visit, $isEmergency);

        $result = $this->send($number, $message);

        if ($result['success']) {
            $visit->forceFill([
                'is_emergency' => $isEmergency || (bool) $visit->is_emergency,
                'sms_sent_at' => now(),
                'sms_status' => $result['provider'] === 'log' ? 'logged' : 'sent',
            ])->save();
        } else {
            $visit->forceFill([
                'sms_status' => 'failed',
            ])->save();
        }

        return array_merge($result, [
            'formatted_message' => $message,
            'recipient' => $number,
        ]);
    }

    /**
     * Format the SMS content following the required clinic template.
     */
    public static function formatVisitMessage(ClinicVisit $visit, bool $isEmergency = false): string
    {
        $student = $visit->student;
        if ($student) {
            $patientName = trim("{$student->first_name} {$student->last_name}");
            $patientId = $student->student_id ?: '';
            $roleLabel = 'Student';
        } elseif ($visit->faculty) {
            $patientName = trim("{$visit->faculty->first_name} {$visit->faculty->last_name}");
            $patientId = $visit->faculty->employee_id ?: '';
            $roleLabel = 'Faculty';
        } elseif ($visit->staff) {
            $patientName = trim("{$visit->staff->first_name} {$visit->staff->last_name}");
            $patientId = $visit->staff->staff_id ?: '';
            $roleLabel = 'Staff';
        } else {
            $patientName = 'Patient';
            $patientId = '';
            $roleLabel = 'Patient';
        }

        $dateFormatted = $visit->visit_date
            ? date('M d, Y', strtotime((string) $visit->visit_date))
            : date('M d, Y');

        $lines = [];
        $lines[] = "TCC CLINIC";
        $lines[] = "DATE: {$dateFormatted}";
        $lines[] = "";
        $lines[] = "Greetings!";
        $lines[] = "";

        if ($isEmergency) {
            $lines[] = "[EMERGENCY MEDICAL ADVISORY]";
            $lines[] = "This is an URGENT notice from TCC Clinic regarding {$roleLabel} {$patientName}" . ($patientId ? " ({$patientId})" : "") . ".";
            $lines[] = "Reason for visit: {$visit->reason}";
            if ($visit->temperature) {
                $lines[] = "Temp: {$visit->temperature}";
            }
            if ($visit->blood_pressure) {
                $lines[] = "BP: {$visit->blood_pressure}";
            }
            if ($visit->treatment) {
                $lines[] = "Treatment: {$visit->treatment}";
            }
            $lines[] = "";
            $lines[] = "EMERGENCY: Please coordinate with or proceed to the TCC Clinic immediately.";
        } else {
            $lines[] = "This is an official notice from TCC Clinic that {$roleLabel} {$patientName}" . ($patientId ? " ({$patientId})" : "") . " visited the clinic today.";
            $lines[] = "Reason for visit: {$visit->reason}";
            if ($visit->treatment) {
                $lines[] = "Treatment: {$visit->treatment}";
            }
            $lines[] = "";
            $lines[] = "Please contact the TCC Clinic if further medical coordination is needed.";
        }

        return implode("\n", $lines);
    }

    /**
     * Dispatch SMS message via configured provider.
     */
    public function send(string $number, string $message): array
    {
        $cleanNumber = $this->sanitizePhoneNumber($number);
        $provider = strtolower(env('SMS_PROVIDER', 'log'));
        $apiKey = env('SEMAPHORE_API_KEY');
        $httpSmsKey = env('HTTPSMS_API_KEY');

        // Auto-switch to configured provider if keys are set
        if (!empty($httpSmsKey) && $provider === 'log') {
            $provider = 'httpsms';
        } elseif (!empty($apiKey) && ($provider === 'log' || $provider === 'semaphore')) {
            $provider = 'semaphore';
        }

        try {
            switch ($provider) {
                case 'httpsms':
                    return $this->sendViaHttpSms($cleanNumber, $message);

                case 'smtp':
                case 'email':
                    return $this->sendViaSmtp($cleanNumber, $message);

                case 'semaphore':
                    return $this->sendViaSemaphore($cleanNumber, $message);

                case 'twilio':
                    return $this->sendViaTwilio($cleanNumber, $message);

                case 'log':
                default:
                    return $this->sendViaLog($cleanNumber, $message);
            }
        } catch (\Throwable $e) {
            Log::error('SMS sending exception: ' . $e->getMessage(), [
                'recipient' => $cleanNumber,
                'provider' => $provider,
            ]);

            return [
                'success' => false,
                'provider' => $provider,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send via Semaphore (Philippines SMS Gateway)
     */
    protected function sendViaSemaphore(string $number, string $message): array
    {
        $apiKey = env('SEMAPHORE_API_KEY');

        if (empty($apiKey)) {
            // Graceful fallback to log if key not yet entered in .env
            Log::info("Semaphore API key missing; falling back to log driver. Recipient: {$number}");
            return $this->sendViaLog($number, $message, 'Semaphore API key not set in .env; message recorded in server log.');
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => $number,
            'message' => $message,
        ];

        $senderName = env('SEMAPHORE_SENDER_NAME');
        if (!empty($senderName)) {
            $payload['sendername'] = $senderName;
        }

        $response = Http::timeout(15)->asForm()->post('https://api.semaphore.co/api/v4/messages', $payload);

        if ($response->successful()) {
            Log::info("SMS sent via Semaphore to {$number}", ['response' => $response->json()]);

            return [
                'success' => true,
                'provider' => 'semaphore',
                'message' => 'SMS sent successfully via Semaphore.',
                'data' => $response->json(),
            ];
        }

        Log::warning("Semaphore SMS failed for {$number}: " . $response->body());

        return [
            'success' => false,
            'provider' => 'semaphore',
            'message' => 'Semaphore API returned an error: ' . ($response->json('message') ?? $response->body()),
        ];
    }

    /**
     * Send via Twilio
     */
    protected function sendViaTwilio(string $number, string $message): array
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_PHONE_NUMBER');

        if (empty($sid) || empty($token) || empty($from)) {
            return $this->sendViaLog($number, $message, 'Twilio credentials not configured; message recorded in server log.');
        }

        // Ensure E.164 format for Twilio (+63...)
        $e164 = $this->toE164($number);

        $response = Http::withBasicAuth($sid, $token)
            ->timeout(15)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $e164,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'provider' => 'twilio',
                'message' => 'SMS sent successfully via Twilio.',
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'provider' => 'twilio',
            'message' => 'Twilio API returned an error: ' . $response->body(),
        ];
    }

    /**
     * Send via httpSMS (Free Android Phone Gateway)
     * Turns an Android phone with SIM unli-promo into an SMS gateway (https://httpsms.com)
     */
    protected function sendViaHttpSms(string $number, string $message): array
    {
        $apiKey = env('HTTPSMS_API_KEY');
        $from = env('HTTPSMS_FROM_PHONE');

        if (empty($apiKey)) {
            Log::info("httpSMS API key not set in .env; falling back to log driver. Recipient: {$number}");
            return $this->sendViaLog($number, $message, 'httpSMS API key not set in .env; message recorded in server log.');
        }

        $e164 = $this->toE164($number);
        $payload = [
            'content' => $message,
            'to' => $e164,
        ];

        if (!empty($from)) {
            $payload['from'] = $this->toE164($from);
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(15)->post('https://api.httpsms.com/v1/messages/send', $payload);

        if ($response->successful()) {
            Log::info("SMS sent via httpSMS to {$e164}", ['response' => $response->json()]);

            return [
                'success' => true,
                'provider' => 'httpsms',
                'message' => 'SMS sent successfully via httpSMS Android gateway.',
                'data' => $response->json(),
            ];
        }

        Log::warning("httpSMS failed for {$e164}: " . $response->body());

        return [
            'success' => false,
            'provider' => 'httpsms',
            'message' => 'httpSMS API error: ' . ($response->json('message') ?? $response->body()),
        ];
    }

    /**
     * Send via SMTP (Free Email / Email-to-SMS Gateway)
     */
    protected function sendViaSmtp(string $number, string $message): array
    {
        // 1. Check if an email-to-sms gateway domain is specified (e.g. @teleflip.com, @carrier.net)
        $gatewayDomain = env('SMS_EMAIL_GATEWAY', '');
        $fallbackEmail = env('SMS_SMTP_FALLBACK_EMAIL', env('MAIL_FROM_ADDRESS'));

        $cleanNumber = $this->sanitizePhoneNumber($number);
        $targetEmail = null;

        if (!empty($gatewayDomain)) {
            // E.g. 09123456789@gateway.domain
            $domain = ltrim($gatewayDomain, '@');
            $targetEmail = "{$cleanNumber}@{$domain}";
        } elseif (!empty($fallbackEmail)) {
            $targetEmail = $fallbackEmail;
        }

        if (empty($targetEmail)) {
            return $this->sendViaLog($number, $message, 'No SMTP recipient or gateway specified in .env; recorded in log.');
        }

        try {
            Mail::raw($message, function ($mail) use ($targetEmail, $cleanNumber) {
                $mail->to($targetEmail)
                    ->subject("TCC Clinic Notice - {$cleanNumber}");
            });

            Log::info("Clinic notice sent via SMTP to {$targetEmail} for phone {$cleanNumber}");

            return [
                'success' => true,
                'provider' => 'smtp',
                'message' => "Message dispatched via SMTP to {$targetEmail}.",
            ];
        } catch (\Throwable $e) {
            Log::error("SMTP dispatch failed: " . $e->getMessage());

            return [
                'success' => false,
                'provider' => 'smtp',
                'message' => 'SMTP dispatch error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Log driver (for development / testing without spending SMS credits)
     */
    protected function sendViaLog(string $number, string $message, ?string $note = null): array
    {
        Log::info("================ [TCC CLINIC SMS DISPATCH] ================\n" .
                  "Recipient: {$number}\n" .
                  "Content:\n{$message}\n" .
                  "===========================================================");

        return [
            'success' => true,
            'provider' => 'log',
            'message' => $note ?: 'SMS recorded successfully in server log (Development/Simulated mode).',
        ];
    }

    /**
     * Normalize number for Philippine / international numbers.
     */
    protected function sanitizePhoneNumber(string $number): string
    {
        // Strip non-digit characters except leading plus
        $hasPlus = str_starts_with(trim($number), '+');
        $digits = preg_replace('/\D/', '', $number);

        // If local 09xxxxxxxxx (11 digits), format to standard
        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }

        // If 639xxxxxxxxx (12 digits), convert to 09xxxxxxxxx for Philippine gateways
        if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
            return '0' . substr($digits, 2);
        }

        return $hasPlus ? '+' . $digits : $digits;
    }

    /**
     * Convert to international E.164 (+63...)
     */
    protected function toE164(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return '+63' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
            return '+' . $digits;
        }

        return str_starts_with($number, '+') ? $number : '+' . $digits;
    }
}

