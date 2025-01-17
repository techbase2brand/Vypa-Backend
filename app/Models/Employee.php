<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $hidden = ['password'];
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'gender',
        'contact_no',
        'joining_date',
        'tag',
        'job_title',
        'logo'
    ];
}
