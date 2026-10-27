<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
    ];

    public function clinicVisits()
    {
        return $this->hasMany(ClinicVisit::class);
    }
}