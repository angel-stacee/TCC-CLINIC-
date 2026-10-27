<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicVisit extends Model
{
    protected $fillable = [
        'student_id',
        'faculty_id',
        'staff_id',
        'nurse_id',
        'contact_number',
        'is_emergency',
        'sms_sent_at',
        'sms_status',
        'visit_date',
        'reason',
        'symptoms',
        'temperature',
        'blood_pressure',
        'assessment',
        'treatment',
        'medicine_id',
        'medicine_quantity',
        'remarks',
    ];

    protected $appends = [
        'cellphone_number',
    ];

    public function getCellphoneNumberAttribute(): ?string
    {
        return $this->contact_number;
    }

    public function setCellphoneNumberAttribute(?string $value): void
    {
        $this->attributes['contact_number'] = $value;
    }

    protected $casts = [
        'visit_date' => 'date:Y-m-d',
        'is_emergency' => 'boolean',
        'sms_sent_at' => 'datetime',
        'medicine_quantity' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}