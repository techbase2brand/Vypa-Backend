<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $hidden = ['password'];
    protected $fillable = [
    ];
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
