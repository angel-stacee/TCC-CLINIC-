<?php

namespace App\Http\Controllers;

use App\Models\ClinicVisit;
use App\Models\Faculty;
use App\Models\Staff;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $totalStaff = Staff::count();
        $totalFaculty = Faculty::count();
        $totalVisits = ClinicVisit::count();
        $maleCount = Student::whereRaw('LOWER(sex) = ?', ['male'])->count()
            + Staff::whereRaw('LOWER(sex) = ?', ['male'])->count()
            + Faculty::whereRaw('LOWER(sex) = ?', ['male'])->count();

        $femaleCount = Student::whereRaw('LOWER(sex) = ?', ['female'])->count()
            + Staff::whereRaw('LOWER(sex) = ?', ['female'])->count()
            + Faculty::whereRaw('LOWER(sex) = ?', ['female'])->count();

        $monthStart = now()->subMonths(7)->startOfMonth()->toDateString();
        $monthlyVisits = ClinicVisit::query()
            ->where('visit_date', '>=', $monthStart)
            ->selectRaw("DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $recentStudents = Student::query()
            ->select([
                'id',
                'student_id',
                'first_name',
                'middle_name',
                'last_name',
                'course',
                'year_level',
                'sex',
                'created_at',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($s) => array_merge($s->toArray(), [
                'patient_type' => 'student',
                'id_number' => $s->student_id,
            ]));

        $recentStaff = Staff::query()
            ->select([
                'id',
                'staff_id',
                'first_name',
                'middle_name',
                'last_name',
                'position',
                'department',
                'sex',
                'created_at',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($st) => array_merge($st->toArray(), [
                'patient_type' => 'staff',
                'id_number' => $st->staff_id,
            ]));

        $recentFaculty = Faculty::query()
            ->select([
                'id',
                'employee_id',
                'first_name',
                'middle_name',
                'last_name',
                'position',
                'department',
                'sex',
                'created_at',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($f) => array_merge($f->toArray(), [
                'patient_type' => 'faculty',
                'id_number' => $f->employee_id,
            ]));

        $recentPatients = $recentStudents
            ->concat($recentStaff)
            ->concat($recentFaculty)
            ->sortByDesc('created_at')
            ->values()
            ->take(5);

        return response()->json([
            'total_students' => $totalStudents,
            'total_patients' => $totalStudents + $totalStaff + $totalFaculty,
            'total_visits' => $totalVisits,
            'total_records' => $totalVisits,
            'student_gender_counts' => [
                'male' => $maleCount,
                'female' => $femaleCount,
            ],
            'patient_gender_counts' => [
                'male' => $maleCount,
                'female' => $femaleCount,
            ],
            'monthly_visits' => $monthlyVisits,
            'recent_students' => $recentPatients,
            'recent_patients' => $recentPatients,
            'recent_visits' => ClinicVisit::query()
                ->select([
                    'id',
                    'student_id',
                    'faculty_id',
                    'staff_id',
                    'visit_date',
                    'reason',
                    'created_at',
                ])
                ->with([
                    'student:id,student_id,first_name,middle_name,last_name',
                    'faculty:id,employee_id,first_name,middle_name,last_name',
                    'staff:id,staff_id,first_name,middle_name,last_name',
                ])
                ->latest('visit_date')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
