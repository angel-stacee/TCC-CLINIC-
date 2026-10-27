<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $query = Student::query()
            ->select([
                'id',
                'student_id',
                'first_name',
                'middle_name',
                'last_name',
                'course',
                'year_level',
                'section',
                'sex',
                'birth_date',
                'contact_number',
                'address',
                'created_at',
                'updated_at',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $contains = '%'.$search.'%';
                $tokens = array_values(array_filter(
                    preg_split('/[\s,]+/', $search),
                    fn ($token) => $token !== '' && $token !== '-'
                ));

                $query->where(function (Builder $subQuery) use ($contains, $tokens) {
                    $subQuery->where('student_id', 'like', $contains)
                        ->orWhere('first_name', 'like', $contains)
                        ->orWhere('middle_name', 'like', $contains)
                        ->orWhere('last_name', 'like', $contains)
                        ->orWhere('course', 'like', $contains)
                        ->orWhere('year_level', 'like', $contains)
                        ->orWhere('section', 'like', $contains);

                    if (count($tokens) > 1) {
                        $subQuery->orWhere(function (Builder $tokenQuery) use ($tokens) {
                            foreach ($tokens as $token) {
                                $tokenContains = '%'.$token.'%';
                                $tokenQuery->where(function (Builder $fieldQuery) use ($tokenContains) {
                                    $fieldQuery->where('student_id', 'like', $tokenContains)
                                        ->orWhere('first_name', 'like', $tokenContains)
                                        ->orWhere('middle_name', 'like', $tokenContains)
                                        ->orWhere('last_name', 'like', $tokenContains)
                                        ->orWhere('course', 'like', $tokenContains)
                                        ->orWhere('year_level', 'like', $tokenContains)
                                        ->orWhere('section', 'like', $tokenContains);
                                });
                            }
                        });
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return response()->json(
            $query->paginate($validated['per_page'] ?? 25)->withQueryString()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|string|max:255|unique:students,student_id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'section' => 'nullable|string|max:255',
            'sex' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'contact_number' => 'required|string|max:50',
            'address' => 'required|string',
        ]);

        $student = Student::create($validated);

        return response()->json($student, 201);
    }

    public function show(Student $student)
    {
        return response()->json(
            $student->load('clinicVisits.nurse')
        );
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'student_id' => 'required|string|max:255|unique:students,student_id,' . $student->id,
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'section' => 'nullable|string|max:255',
            'sex' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'contact_number' => 'required|string|max:50',
            'address' => 'required|string',
        ]);

        $student->update($validated);

        return response()->json($student->fresh());
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully.'
        ]);
    }
}
