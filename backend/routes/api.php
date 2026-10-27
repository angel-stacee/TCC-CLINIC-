<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\ClinicVisitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\AiChatbotController;
use App\Http\Controllers\ReportController;

Route::get('/login', function () {
    $frontendUrl = rtrim((string) config('services.frontend.url', 'http://localhost:5173'), '/');

    return redirect()->away($frontendUrl . '/login');
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password/send-otp', [AuthController::class, 'sendResetOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPasswordWithOtp']);
             
Route::get('/test-students', function () {
    return response()->json(
        \App\Models\Student::with('clinicVisits')
            ->latest()
            ->paginate(25)
    );
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/clinic-visit-options', [ClinicVisitController::class, 'options']);
    Route::apiResource('students', StudentController::class);
    Route::apiResource('staff', StaffController::class);
    Route::apiResource('faculties', FacultyController::class);
    Route::apiResource('clinic-visits', ClinicVisitController::class);
    Route::post('/clinic-visits/{clinicVisit}/send-sms', [ClinicVisitController::class, 'sendSms']);
    Route::get('/nurses', [ClinicVisitController::class, 'nurses']);
    Route::apiResource('medicines', MedicineController::class);
    Route::post('/ai-chat', [AiChatbotController::class, 'chat']);
    Route::get('/reports/monthly', [ReportController::class, 'monthly']);
});
