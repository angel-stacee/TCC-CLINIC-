<?php

namespace App\Http\Controllers;

use App\Models\ClinicVisit;
use App\Models\Faculty;
use App\Models\Medicine;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicVisitController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');
        $dateSearch = $this->normalizeVisitDateSearch($search);

        $query = ClinicVisit::query()
            ->select([
                'id',
                'student_id',
                'faculty_id',
                'staff_id',
                'nurse_id',
                'contact_number',
                'is_emergency',
                'sms_sent_at',
                'sms_status',
                'medicine_id',
                'medicine_quantity',
                'visit_date',
                'reason',
                'symptoms',
                'temperature',
                'blood_pressure',
                'assessment',
                'treatment',
                'remarks',
                'created_at',
                'updated_at',
            ])
            ->with([
                'student:id,student_id,first_name,middle_name,last_name,contact_number',
                'faculty:id,employee_id,first_name,middle_name,last_name,contact_number',
                'staff:id,staff_id,first_name,middle_name,last_name,contact_number',
                'nurse:id,name,email,role',
                'medicine:id,medicine_name,unit',
            ])
            ->when($validated['student_id'] ?? null, fn (Builder $query, int $id) => $query->where('student_id', $id))
            ->when($validated['faculty_id'] ?? null, fn (Builder $query, int $id) => $query->where('faculty_id', $id))
            ->when($validated['staff_id'] ?? null, fn (Builder $query, int $id) => $query->where('staff_id', $id))
            ->when($search !== '', function (Builder $query) use ($search, $dateSearch) {
                $term = $search;
                $contains = '%'.$term.'%';
                $tokens = array_values(array_filter(
                    preg_split('/[\s,]+/', $term),
                    fn ($token) => $token !== '' && $token !== '-'
                ));

                $query->where(function (Builder $query) use ($contains, $dateSearch, $tokens) {
                    $query->where('reason', 'like', $contains)
                        ->orWhere('symptoms', 'like', $contains)
                        ->orWhere('temperature', 'like', $contains)
                        ->orWhere('blood_pressure', 'like', $contains)
                        ->orWhere('assessment', 'like', $contains)
                        ->orWhere('treatment', 'like', $contains)
                        ->orWhere('remarks', 'like', $contains)
                        ->orWhere('visit_date', 'like', $contains)
                        ->orWhere('contact_number', 'like', $contains)
                        ->when($dateSearch, fn (Builder $dateQuery, string $date) => $dateQuery->orWhereDate('visit_date', $date))
                        ->orWhereRaw('CAST(medicine_quantity AS CHAR) LIKE ?', [$contains])
                        ->orWhereHas('student', function (Builder $studentQuery) use ($contains, $tokens) {
                            $studentQuery->where(function (Builder $sq) use ($contains, $tokens) {
                                $sq->where('student_id', 'like', $contains)
                                    ->orWhere('first_name', 'like', $contains)
                                    ->orWhere('middle_name', 'like', $contains)
                                    ->orWhere('last_name', 'like', $contains)
                                    ->orWhere('contact_number', 'like', $contains);

                                if (count($tokens) > 1) {
                                    $sq->orWhere(function (Builder $tq) use ($tokens) {
                                        foreach ($tokens as $token) {
                                            $tokenContains = '%'.$token.'%';
                                            $tq->where(function (Builder $fq) use ($tokenContains) {
                                                $fq->where('student_id', 'like', $tokenContains)
                                                    ->orWhere('first_name', 'like', $tokenContains)
                                                    ->orWhere('middle_name', 'like', $tokenContains)
                                                    ->orWhere('last_name', 'like', $tokenContains);
                                            });
                                        }
                                    });
                                }
                            });
                        })
                        ->orWhereHas('faculty', function (Builder $facultyQuery) use ($contains, $tokens) {
                            $facultyQuery->where(function (Builder $fq) use ($contains, $tokens) {
                                $fq->where('employee_id', 'like', $contains)
                                    ->orWhere('first_name', 'like', $contains)
                                    ->orWhere('middle_name', 'like', $contains)
                                    ->orWhere('last_name', 'like', $contains)
                                    ->orWhere('contact_number', 'like', $contains);

                                if (count($tokens) > 1) {
                                    $fq->orWhere(function (Builder $tq) use ($tokens) {
                                        foreach ($tokens as $token) {
                                            $tokenContains = '%'.$token.'%';
                                            $tq->where(function (Builder $fieldQuery) use ($tokenContains) {
                                                $fieldQuery->where('employee_id', 'like', $tokenContains)
                                                    ->orWhere('first_name', 'like', $tokenContains)
                                                    ->orWhere('middle_name', 'like', $tokenContains)
                                                    ->orWhere('last_name', 'like', $tokenContains)
                                                    ->orWhere('contact_number', 'like', $tokenContains);
                                            });
                                        }
                                    });
                                }
                            });
                        })
                        ->orWhereHas('staff', function (Builder $staffQuery) use ($contains, $tokens) {
                            $staffQuery->where(function (Builder $sq) use ($contains, $tokens) {
                                $sq->where('staff_id', 'like', $contains)
                                    ->orWhere('first_name', 'like', $contains)
                                    ->orWhere('middle_name', 'like', $contains)
                                    ->orWhere('last_name', 'like', $contains)
                                    ->orWhere('contact_number', 'like', $contains);

                                if (count($tokens) > 1) {
                                    $sq->orWhere(function (Builder $tq) use ($tokens) {
                                        foreach ($tokens as $token) {
                                            $tokenContains = '%'.$token.'%';
                                            $tq->where(function (Builder $fieldQuery) use ($tokenContains) {
                                                $fieldQuery->where('staff_id', 'like', $tokenContains)
                                                    ->orWhere('first_name', 'like', $tokenContains)
                                                    ->orWhere('middle_name', 'like', $tokenContains)
                                                    ->orWhere('last_name', 'like', $tokenContains)
                                                    ->orWhere('contact_number', 'like', $tokenContains);
                                            });
                                        }
                                    });
                                }
                            });
                        })
                        ->orWhereHas('nurse', fn (Builder $nurseQuery) => $nurseQuery->where('name', 'like', $contains))
                        ->orWhereHas('medicine', fn (Builder $medicineQuery) => $medicineQuery->where('medicine_name', 'like', $contains));
                });
            })
            ->latest('visit_date')
            ->latest('id');

        return response()->json(
            $query->paginate($validated['per_page'] ?? 25)->withQueryString()
        );
    }

    public function store(Request $request)
    {
        if ($request->filled('cellphone_number') && ! $request->filled('contact_number')) {
            $request->merge(['contact_number' => $request->input('cellphone_number')]);
        }

        if (! $request->filled('contact_number')) {
            $patientContact = null;
            if ($request->filled('student_id')) {
                $patientContact = Student::where('id', $request->input('student_id'))->value('contact_number');
            } elseif ($request->filled('faculty_id')) {
                $patientContact = Faculty::where('id', $request->input('faculty_id'))->value('contact_number');
            } elseif ($request->filled('staff_id')) {
                $patientContact = Staff::where('id', $request->input('staff_id'))->value('contact_number');
            }

            if (! empty($patientContact)) {
                $request->merge(['contact_number' => $patientContact]);
            }
        }

        $validated = $request->validate([
            'student_id' => [
                'nullable',
                'exists:students,id',
            ],

            'faculty_id' => [
                'nullable',
                'exists:faculties,id',
            ],

            'staff_id' => [
                'nullable',
                'exists:staff,id',
            ],

            'nurse_id' => [
                'required',
                'exists:users,id',
            ],

            'contact_number' => [
                'required',
                'string',
                'max:50',
            ],

            'cellphone_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'visit_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'reason' => [
                'required',
                'string',
                'max:1000',
            ],

            'symptoms' => [
                'nullable',
                'string',
            ],

            'temperature' => [
                'nullable',
                'string',
                'max:50',
            ],

            'blood_pressure' => [
                'nullable',
                'string',
                'max:50',
            ],

            'assessment' => [
                'nullable',
                'string',
            ],

            'treatment' => [
                'nullable',
                'string',
            ],

            'medicine_id' => [
                'nullable',
                'exists:medicines,id',
            ],

            'medicine_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'is_emergency' => [
                'nullable',
                'boolean',
            ],

            'send_sms' => [
                'nullable',
                'boolean',
            ],
        ], [
            'contact_number.required' => 'The cellphone number is required.',
        ]);

        $patientIds = [
            'student_id' => $validated['student_id'] ?? null,
            'faculty_id' => $validated['faculty_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
        ];

        $selectedPatients = collect($patientIds)
            ->filter(fn ($value) => ! empty($value))
            ->count();

        if ($selectedPatients === 0) {
            throw ValidationException::withMessages([
                'patient' => [
                    'Please select a Student, Faculty, or Staff.',
                ],
            ]);
        }

        if ($selectedPatients > 1) {
            throw ValidationException::withMessages([
                'patient' => [
                    'Please select only one patient.',
                ],
            ]);
        }

        if (array_key_exists('cellphone_number', $validated)) {
            $validated['contact_number'] = $validated['contact_number'] ?? $validated['cellphone_number'];
            unset($validated['cellphone_number']);
        }

        $validated = $this->normalizeMedicineData($validated);

        $this->validateNurse($validated['nurse_id']);

        $visit = DB::transaction(function () use ($validated) {

            if (! empty($validated['medicine_id'])) {

                $medicine = Medicine::where(
                    'id',
                    $validated['medicine_id']
                )
                    ->lockForUpdate()
                    ->first();

                if (! $medicine) {
                    throw ValidationException::withMessages([
                        'medicine_id' => [
                            'Selected medicine was not found.',
                        ],
                    ]);
                }

                $quantity = (int) $validated['medicine_quantity'];

                $currentStock = (int) $medicine->stock;

                if ($currentStock < $quantity) {
                    throw ValidationException::withMessages([
                        'medicine_quantity' => [
                            "Insufficient stock for {$medicine->medicine_name}. Available stock: {$currentStock}.",
                        ],
                    ]);
                }

                $medicine->stock =
                    $currentStock - $quantity;

                $medicine->save();
            }

            return ClinicVisit::create($validated);
        });

        $shouldSendSms = $request->has('send_sms')
            ? $request->boolean('send_sms')
            : true;

        $isEmergency = $request->boolean('is_emergency');

        if ($shouldSendSms && ! empty($visit->contact_number)) {
            try {
                app(SmsService::class)->sendVisitNotification($visit, $isEmergency);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Clinic visit SMS dispatch error: '.$e->getMessage());
            }
        }

        return response()->json(
            $visit->fresh()->load([
                'student',
                'faculty',
                'staff',
                'nurse',
                'medicine',
            ]),
            201
        );
    }

    public function show(ClinicVisit $clinicVisit)
    {
        return response()->json(
            $clinicVisit->load([
                'student',
                'faculty',
                'staff',
                'nurse',
                'medicine',
            ])
        );
    }

    public function update(
        Request $request,
        ClinicVisit $clinicVisit
    ) {
        if ($request->filled('cellphone_number') && ! $request->filled('contact_number')) {
            $request->merge(['contact_number' => $request->input('cellphone_number')]);
        }

        if (! $request->filled('contact_number')) {
            $patientContact = $clinicVisit->contact_number;
            $studentId = $request->input('student_id', $clinicVisit->student_id);
            $facultyId = $request->input('faculty_id', $clinicVisit->faculty_id);
            $staffId = $request->input('staff_id', $clinicVisit->staff_id);

            if (empty($patientContact)) {
                if (! empty($studentId)) {
                    $patientContact = Student::where('id', $studentId)->value('contact_number');
                } elseif (! empty($facultyId)) {
                    $patientContact = Faculty::where('id', $facultyId)->value('contact_number');
                } elseif (! empty($staffId)) {
                    $patientContact = Staff::where('id', $staffId)->value('contact_number');
                }
            }

            if (! empty($patientContact)) {
                $request->merge(['contact_number' => $patientContact]);
            }
        }

        $validated = $request->validate([
            'student_id' => [
                'nullable',
                'exists:students,id',
            ],

            'faculty_id' => [
                'nullable',
                'exists:faculties,id',
            ],

            'staff_id' => [
                'nullable',
                'exists:staff,id',
            ],

            'nurse_id' => [
                'required',
                'exists:users,id',
            ],

            'contact_number' => [
                'required',
                'string',
                'max:50',
            ],

            'cellphone_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'visit_date' => [
                'required',
                'date',
            ],

            'reason' => [
                'required',
                'string',
                'max:1000',
            ],

            'symptoms' => [
                'nullable',
                'string',
            ],

            'temperature' => [
                'nullable',
                'string',
                'max:50',
            ],

            'blood_pressure' => [
                'nullable',
                'string',
                'max:50',
            ],

            'assessment' => [
                'nullable',
                'string',
            ],

            'treatment' => [
                'nullable',
                'string',
            ],

            'medicine_id' => [
                'nullable',
                'exists:medicines,id',
            ],

            'medicine_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'is_emergency' => [
                'nullable',
                'boolean',
            ],

            'send_sms' => [
                'nullable',
                'boolean',
            ],
        ], [
            'contact_number.required' => 'The cellphone number is required.',
        ]);

        $patientIds = [
            'student_id' => $validated['student_id'] ?? null,
            'faculty_id' => $validated['faculty_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
        ];

        $selectedPatients = collect($patientIds)
            ->filter(fn ($value) => ! empty($value))
            ->count();

        if ($selectedPatients === 0) {
            throw ValidationException::withMessages([
                'patient' => [
                    'Please select a Student, Faculty, or Staff.',
                ],
            ]);
        }

        if ($selectedPatients > 1) {
            throw ValidationException::withMessages([
                'patient' => [
                    'Please select only one patient.',
                ],
            ]);
        }

        if (array_key_exists('cellphone_number', $validated)) {
            $validated['contact_number'] = $validated['contact_number'] ?? $validated['cellphone_number'];
            unset($validated['cellphone_number']);
        }

        $validated = $this->normalizeMedicineData($validated);

        $this->validateNurse($validated['nurse_id']);

        DB::transaction(function () use (
            $validated,
            $clinicVisit
        ) {

            $oldMedicineId =
                $clinicVisit->medicine_id;

            $oldQuantity = (int) (
                $clinicVisit->medicine_quantity ?? 0
            );

            $newMedicineId =
                $validated['medicine_id'] ?? null;

            $newQuantity = $newMedicineId
                ? (int) (
                    $validated['medicine_quantity'] ?? 0
                )
                : 0;

            $medicineIds = collect([
                $oldMedicineId,
                $newMedicineId,
            ])
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $medicines = $medicineIds->isEmpty()
                ? collect()
                : Medicine::whereIn(
                    'id',
                    $medicineIds
                )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

            if (
                $oldMedicineId &&
                $oldQuantity > 0
            ) {

                $oldMedicine =
                    $medicines->get(
                        $oldMedicineId
                    );

                if (! $oldMedicine) {
                    throw ValidationException::withMessages([
                        'medicine_id' => [
                            'The previously selected medicine no longer exists.',
                        ],
                    ]);
                }

                $oldMedicine->stock =
                    (int) $oldMedicine->stock +
                    $oldQuantity;

                $oldMedicine->save();
            }

            if (
                $newMedicineId &&
                $newQuantity > 0
            ) {

                $newMedicine =
                    $medicines->get(
                        $newMedicineId
                    );

                if (! $newMedicine) {
                    throw ValidationException::withMessages([
                        'medicine_id' => [
                            'Selected medicine was not found.',
                        ],
                    ]);
                }

                $availableStock =
                    (int) $newMedicine->stock;

                if (
                    $availableStock <
                    $newQuantity
                ) {
                    throw ValidationException::withMessages([
                        'medicine_quantity' => [
                            "Insufficient stock for {$newMedicine->medicine_name}. Available stock: {$availableStock}.",
                        ],
                    ]);
                }

                $newMedicine->stock =
                    $availableStock -
                    $newQuantity;

                $newMedicine->save();
            }

            $clinicVisit->update(
                $validated
            );
        });

        $shouldSendSms = $request->has('send_sms')
            ? $request->boolean('send_sms')
            : true;

        $isEmergency = $request->has('is_emergency')
            ? $request->boolean('is_emergency')
            : (bool) $clinicVisit->is_emergency;

        if ($shouldSendSms && ! empty($clinicVisit->contact_number)) {
            try {
                app(SmsService::class)->sendVisitNotification($clinicVisit->fresh(), $isEmergency);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Clinic visit SMS dispatch error on update: '.$e->getMessage());
            }
        }

        return response()->json(
            $clinicVisit
                ->fresh()
                ->load([
                    'student',
                    'faculty',
                    'staff',
                    'nurse',
                    'medicine',
                ])
        );
    }

    public function destroy(
        ClinicVisit $clinicVisit
    ) {
        DB::transaction(
            function () use ($clinicVisit) {

                $medicineId =
                    $clinicVisit->medicine_id;

                $quantity = (int) (
                    $clinicVisit->medicine_quantity ?? 0
                );

                if (
                    $medicineId &&
                    $quantity > 0
                ) {

                    $medicine =
                        Medicine::where(
                            'id',
                            $medicineId
                        )
                            ->lockForUpdate()
                            ->first();

                    if ($medicine) {

                        $medicine->stock =
                            (int) $medicine->stock +
                            $quantity;

                        $medicine->save();
                    }
                }

                $clinicVisit->delete();
            }
        );

        return response()->json([
            'message' => 'Clinic visit deleted successfully.',
        ]);
    }

    private function normalizeMedicineData(
        array $validated
    ): array {

        if (! empty($validated['medicine_id'])) {

            if (
                empty(
                    $validated['medicine_quantity']
                ) ||
                (int) $validated[
                    'medicine_quantity'
                ] < 1
            ) {

                throw ValidationException::withMessages([
                    'medicine_quantity' => [
                        'Quantity used must be at least 1.',
                    ],
                ]);
            }

            $validated['medicine_quantity'] =
                (int) $validated[
                    'medicine_quantity'
                ];

        } else {

            $validated['medicine_id'] = null;

            $validated['medicine_quantity'] = null;
        }

        return $validated;
    }

    private function normalizeVisitDateSearch(string $search): ?string
    {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $search, $matches)) {
            [$month, $day, $year] = [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $search, $matches)) {
            [$year, $month, $day] = [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        } else {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function validateNurse(
        $nurseId
    ) {
        $nurse = User::where('id', $nurseId)->first();

        if (! $nurse) {
            throw ValidationException::withMessages([
                'nurse_id' => [
                    'Selected nurse or attendant was not found.',
                ],
            ]);
        }
    }

    public function nurses()
    {
        return response()->json(
            User::query()
                ->select(
                    'id',
                    'name',
                    'email',
                    'role'
                )
                ->orderBy('name')
                ->get()
        );
    }

    public function options(Request $request)
    {
        $validated = $request->validate([
            'patient_search' => ['nullable', 'string', 'max:100'],
            'medicine_search' => ['nullable', 'string', 'max:100'],
        ]);

        $patientSearch = trim($validated['patient_search'] ?? '');
        $medicineSearch = trim($validated['medicine_search'] ?? '');
        $patientTokens = array_values(array_filter(
            preg_split('/[\s,]+/', $patientSearch),
            fn ($token) => $token !== '' && $token !== '-'
        ));
        $payload = [];

        if (! $request->has('medicine_search')) {
            $payload['students'] = Student::query()
                ->select(['id', 'student_id', 'first_name', 'middle_name', 'last_name', 'contact_number'])
                ->when($patientSearch !== '', function (Builder $query) use ($patientSearch, $patientTokens) {
                    $contains = '%'.$patientSearch.'%';
                    $query->where(function (Builder $subQuery) use ($contains, $patientTokens) {
                        $subQuery->where('student_id', 'like', $contains)
                            ->orWhere('first_name', 'like', $contains)
                            ->orWhere('middle_name', 'like', $contains)
                            ->orWhere('last_name', 'like', $contains);

                        if (count($patientTokens) > 1) {
                            $subQuery->orWhere(function (Builder $tokenQuery) use ($patientTokens) {
                                foreach ($patientTokens as $token) {
                                    $tokenContains = '%'.$token.'%';
                                    $tokenQuery->where(function (Builder $fieldQuery) use ($tokenContains) {
                                        $fieldQuery->where('student_id', 'like', $tokenContains)
                                            ->orWhere('first_name', 'like', $tokenContains)
                                            ->orWhere('middle_name', 'like', $tokenContains)
                                            ->orWhere('last_name', 'like', $tokenContains);
                                    });
                                }
                            });
                        }
                    });
                })
                ->orderBy('last_name')
                ->orderBy('id')
                ->limit(10)
                ->get();

            $payload['staff'] = Staff::query()
                ->select(['id', 'staff_id', 'first_name', 'middle_name', 'last_name', 'contact_number'])
                ->when($patientSearch !== '', function (Builder $query) use ($patientSearch, $patientTokens) {
                    $contains = '%'.$patientSearch.'%';
                    $query->where(function (Builder $subQuery) use ($contains, $patientTokens) {
                        $subQuery->where('staff_id', 'like', $contains)
                            ->orWhere('first_name', 'like', $contains)
                            ->orWhere('middle_name', 'like', $contains)
                            ->orWhere('last_name', 'like', $contains);

                        if (count($patientTokens) > 1) {
                            $subQuery->orWhere(function (Builder $tokenQuery) use ($patientTokens) {
                                foreach ($patientTokens as $token) {
                                    $tokenContains = '%'.$token.'%';
                                    $tokenQuery->where(function (Builder $fieldQuery) use ($tokenContains) {
                                        $fieldQuery->where('staff_id', 'like', $tokenContains)
                                            ->orWhere('first_name', 'like', $tokenContains)
                                            ->orWhere('middle_name', 'like', $tokenContains)
                                            ->orWhere('last_name', 'like', $tokenContains);
                                    });
                                }
                            });
                        }
                    });
                })
                ->orderBy('last_name')
                ->orderBy('id')
                ->limit(10)
                ->get();

            $payload['faculties'] = Faculty::query()
                ->select(['id', 'employee_id', 'first_name', 'middle_name', 'last_name', 'contact_number'])
                ->when($patientSearch !== '', function (Builder $query) use ($patientSearch, $patientTokens) {
                    $contains = '%'.$patientSearch.'%';
                    $query->where(function (Builder $subQuery) use ($contains, $patientTokens) {
                        $subQuery->where('employee_id', 'like', $contains)
                            ->orWhere('first_name', 'like', $contains)
                            ->orWhere('middle_name', 'like', $contains)
                            ->orWhere('last_name', 'like', $contains);

                        if (count($patientTokens) > 1) {
                            $subQuery->orWhere(function (Builder $tokenQuery) use ($patientTokens) {
                                foreach ($patientTokens as $token) {
                                    $tokenContains = '%'.$token.'%';
                                    $tokenQuery->where(function (Builder $fieldQuery) use ($tokenContains) {
                                        $fieldQuery->where('employee_id', 'like', $tokenContains)
                                            ->orWhere('first_name', 'like', $tokenContains)
                                            ->orWhere('middle_name', 'like', $tokenContains)
                                            ->orWhere('last_name', 'like', $tokenContains);
                                    });
                                }
                            });
                        }
                    });
                })
                ->orderBy('last_name')
                ->orderBy('id')
                ->limit(10)
                ->get();
        }

        if (! $request->has('patient_search')) {
            $payload['medicines'] = Medicine::query()
                ->select(['id', 'medicine_name', 'treatment_type', 'unit', 'stock', 'minimum_stock'])
                ->when($medicineSearch !== '', fn (Builder $query) => $query->where('medicine_name', 'like', '%'.$medicineSearch.'%'))
                ->orderBy('medicine_name')
                ->limit(10)
                ->get();
        }

        if (! $request->has('patient_search') && ! $request->has('medicine_search')) {
            $payload['nurses'] = User::query()
                ->select(['id', 'name', 'email', 'role'])
                ->orderBy('name')
                ->get();
            $payload['totals'] = [
                'students' => Student::count(),
                'staff' => Staff::count(),
                'faculties' => Faculty::count(),
            ];
        }

        return response()->json($payload);
    }

    public function sendSms(Request $request, ClinicVisit $clinicVisit, SmsService $smsService)
    {
        $validated = $request->validate([
            'contact_number' => ['nullable', 'string', 'max:50'],
            'is_emergency' => ['nullable', 'boolean'],
        ]);

        $isEmergency = $request->has('is_emergency')
            ? $request->boolean('is_emergency')
            : (bool) $clinicVisit->is_emergency;

        $recipient = $validated['contact_number'] ?? $clinicVisit->contact_number;

        if (empty($recipient)) {
            return response()->json([
                'message' => 'No cellphone number available for this clinic visit.',
            ], 422);
        }

        $result = $smsService->sendVisitNotification($clinicVisit, $isEmergency, $recipient);

        return response()->json([
            'message' => $result['message'],
            'result' => $result,
            'visit' => $clinicVisit->fresh()->load([
                'student',
                'faculty',
                'staff',
                'nurse',
                'medicine',
            ]),
        ]);
    }
}
