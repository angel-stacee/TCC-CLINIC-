<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
       
                          
       
    public function up(): void
    {
    Schema::create('students', function (Blueprint $table) {
    $table->id();
    $table->string('student_id')->unique();
    $table->string('first_name');
    $table->string('middle_name')->nullable();
    $table->string('last_name');
    $table->string('course');
    $table->string('year_level');
    $table->string('section')->nullable();
    $table->string('sex')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('contact_number')->nullable();
    $table->string('address')->nullable();
    $table->timestamps();
});
    }

   
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
