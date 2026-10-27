<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'staff_id',
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
}