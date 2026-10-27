<?php

namespace Tests\Feature;

use App\Models\ClinicVisit;
use App\Models\Medicine;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicineReportsVisitsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Student $student;

    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'Nurse',
        ]);
        Sanctum::actingAs($this->user);

        $this->student = Student::create([
            'student_id' => '2026-0001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'course' => 'BSIT',
            'year_level' => '1st Year',
            'section' => 'A',
            'sex' => 'Male',
            'birth_date' => '2005-01-01',
            'contact_number' => '09123456789',
            'address' => 'Test Address',
        ]);

        $this->medicine = Medicine::create([
            'medicine_name' => 'Paracetamol 500mg',
            'treatment_type' => 'Medication',
            'unit' => 'Tablet',
            'stock' => 50,
            'minimum_stock' => 10,
            'description' => 'For fever and pain',
        ]);
    }

    public function test_can_list_and_manage_medicines(): void
    {
        $response = $this->getJson('/api/medicines?include_summary=1');
        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'summary' => [
                'total_medicines',
                'total_stock',
                'low_stock_count',
                'out_of_stock_count',
            ],
        ]);

        // Add medicine
        $createRes = $this->postJson('/api/medicines', [
            'medicine_name' => 'Amoxicillin 500mg',
            'treatment_type' => 'Medication',
            'unit' => 'Capsule',
            'stock' => 30,
            'minimum_stock' => 5,
        ]);
        $createRes->assertCreated();

        // Update medicine
        $updateRes = $this->putJson("/api/medicines/{$this->medicine->id}", [
            'medicine_name' => 'Paracetamol 500mg (Updated)',
            'treatment_type' => 'Medication',
            'unit' => 'Tablet',
            'stock' => 45,
            'minimum_stock' => 10,
        ]);
        $updateRes->assertOk();
    }

    public function test_can_record_clinic_visit_and_auto_deduct_stock(): void
    {
        $today = Carbon::today()->toDateString();

        $visitRes = $this->postJson('/api/clinic-visits', [
            'student_id' => $this->student->id,
            'nurse_id' => $this->user->id,
            'visit_date' => $today,
            'reason' => 'Headache and fever',
            'treatment' => 'Medication',
            'medicine_id' => $this->medicine->id,
            'medicine_quantity' => 2,
        ]);

        $visitRes->assertCreated();
        $this->medicine->refresh();
        $this->assertEquals(48, $this->medicine->stock);
    }

    public function test_can_record_clinic_visit_without_medicine(): void
    {
        $today = Carbon::today()->toDateString();

        $visitRes = $this->postJson('/api/clinic-visits', [
            'student_id' => $this->student->id,
            'nurse_id' => $this->user->id,
            'visit_date' => $today,
            'reason' => 'Bruised ankle',
            'treatment' => 'Cold Compress',
        ]);

        $visitRes->assertCreated();
    }

    public function test_can_record_clinic_visit_for_yesterday(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $visitRes = $this->postJson('/api/clinic-visits', [
            'student_id' => $this->student->id,
            'nurse_id' => $this->user->id,
            'visit_date' => $yesterday,
            'reason' => 'Stomach ache',
            'treatment' => 'Observation',
        ]);

        $visitRes->assertCreated();
    }

    public function test_monthly_report_generation(): void
    {
        $today = Carbon::today();

        // Record a visit
        ClinicVisit::create([
            'student_id' => $this->student->id,
            'nurse_id' => $this->user->id,
            'visit_date' => $today->toDateString(),
            'reason' => 'Fever',
            'treatment' => 'Medication',
            'medicine_id' => $this->medicine->id,
            'medicine_quantity' => 1,
        ]);

        $reportRes = $this->getJson("/api/reports/monthly?month={$today->month}&year={$today->year}");
        $reportRes->assertOk();
        $reportRes->assertJsonStructure([
            'report',
            'summary',
            'reasons',
            'medicine_usage',
            'daily_visits',
            'nurse_activity',
            'visits',
        ]);
        $this->assertGreaterThanOrEqual(1, $reportRes->json('summary.total_visits'));
    }
}
