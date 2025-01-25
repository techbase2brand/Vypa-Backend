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
        'gender',
        'contact_no',
        'password',
        'shop_id', // Ensure this is included
        'joining_date',
        'job_title',
        'tag',
        'logo',
        'owner_id'
    ];
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
