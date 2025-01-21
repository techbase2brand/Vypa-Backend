<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
use HasFactory;

protected $fillable = ['name', 'slug', 'tag'];

// Define the many-to-many relationship with User
public function users()
{
return $this->belongsToMany(User::class, 'group_user');
}
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            $group->slug = Str::slug($group->name);
        });
    }
}
