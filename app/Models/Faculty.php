<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = [
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'position',
        'department',
        'sex',
        'birth_date',
        'contact_number',
        'address',
    ];

    public function clinicVisits()
    {
        return $this->hasMany(ClinicVisit::class);
    }
}