<?php

namespace Tests\Feature;

use App\Models\ClinicVisit;
use App\Models\Faculty;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchPatientTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'Nurse',
        ]);
        Sanctum::actingAs($this->user);
    }

    public function test_can_search_student_by_id_name_or_combined(): void
    {
        Student::create([
            'student_id' => '2024-0001',
            'first_name' => 'Juan',
            'middle_name' => 'Protacio',
            'last_name' => 'Dela Cruz',
            'course' => 'BSIT',
            'year_level' => '3rd Year',
            'section' => 'A',
            'sex' => 'Male',
            'birth_date' => '2002-05-10',
            'contact_number' => '09123456789',
            'address' => 'Sample Address',
        ]);

        Student::create([
            'student_id' => '2024-0002',
            'first_name' => 'Maria',
            'middle_name' => 'Clara',
            'last_name' => 'Santos',
            'course' => 'BSBA',
            'year_level' => '2nd Year',
            'section' => 'B',
            'sex' => 'Female',
            'birth_date' => '2003-08-15',
            'contact_number' => '09987654321',
            'address' => 'Another Address',
        ]);

        // Search by student ID
        $response = $this->getJson('/api/students?search=2024-0001');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2024-0001', $response->json('data.0.student_id'));

        // Search by partial ID
        $response = $this->getJson('/api/students?search=0001');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2024-0001', $response->json('data.0.student_id'));

        // Search by first name and last name (ignoring middle name)
        $response = $this->getJson('/api/students?search=Juan Dela Cruz');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2024-0001', $response->json('data.0.student_id'));

        // Search by ID and Name together
        $response = $this->getJson('/api/students?search=2024-0001 Juan');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2024-0001', $response->json('data.0.student_id'));

        // Search by ID - Name (format used when patient is selected)
        $response = $this->getJson('/api/students?search=2024-0001 - Juan Dela Cruz');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2024-0001', $response->json('data.0.student_id'));
    }

    public function test_clinic_visit_options_patient_search_matches_id_and_name(): void
    {
        Student::create([
            'student_id' => '2024-0001',
            'first_name' => 'Juan',
            'middle_name' => 'Protacio',
            'last_name' => 'Dela Cruz',
            'course' => 'BSIT',
            'year_level' => '3rd Year',
            'sex' => 'Male',
            'birth_date' => '2002-05-10',
            'contact_number' => '09123456789',
            'address' => 'Address',
        ]);

        Staff::create([
            'staff_id' => 'STF-001',
            'employee_id' => 'STF-001',
            'first_name' => 'Pedro',
            'middle_name' => '',
            'last_name' => 'Penduko',
            'position' => 'Staff',
            'department' => 'Admin',
            'sex' => 'Male',
            'birth_date' => '1990-01-01',
            'contact_number' => '09111111111',
            'address' => 'Staff Address',
        ]);

        Faculty::create([
            'employee_id' => 'FAC-001',
            'first_name' => 'Jose',
            'middle_name' => 'Protacio',
            'last_name' => 'Rizal',
            'position' => 'Instructor',
            'department' => 'College of Arts',
            'sex' => 'Male',
            'birth_date' => '1985-06-19',
            'contact_number' => '09222222222',
            'address' => 'Faculty Address',
        ]);

        // Search student by ID - Name
        $response = $this->getJson('/api/clinic-visit-options?patient_search=2024-0001 - Juan Dela Cruz');
        $response->assertOk();
        $response->assertJsonCount(1, 'students');
        $this->assertEquals('2024-0001', $response->json('students.0.student_id'));

        // Search staff by ID and Name
        $response = $this->getJson('/api/clinic-visit-options?patient_search=STF-001 Pedro');
        $response->assertOk();
        $response->assertJsonCount(1, 'staff');
        $this->assertEquals('STF-001', $response->json('staff.0.staff_id'));

        // Search faculty by partial ID
        $response = $this->getJson('/api/clinic-visit-options?patient_search=FAC-001');
        $response->assertOk();
        $response->assertJsonCount(1, 'faculties');
        $this->assertEquals('FAC-001', $response->json('faculties.0.employee_id'));
    }

    public function test_clinic_visits_table_search_by_patient_id_and_name(): void
    {
        $student = Student::create([
            'student_id' => '2024-0001',
            'first_name' => 'Juan',
            'middle_name' => 'Protacio',
            'last_name' => 'Dela Cruz',
            'course' => 'BSIT',
            'year_level' => '3rd Year',
            'sex' => 'Male',
            'birth_date' => '2002-05-10',
            'contact_number' => '09123456789',
            'address' => 'Address',
        ]);

        ClinicVisit::create([
            'student_id' => $student->id,
            'nurse_id' => $this->user->id,
            'visit_date' => '2026-10-10',
            'reason' => 'Headache',
            'symptoms' => 'Dizziness',
            'temperature' => '36.5',
            'blood_pressure' => '120/80',
            'treatment' => 'Rest',
            'remarks' => 'Good',
        ]);

        // Search by patient ID and Name
        $response = $this->getJson('/api/clinic-visits?search=2024-0001 Juan');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        // Search by patient full name
        $response = $this->getJson('/api/clinic-visits?search=Juan Dela Cruz');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
