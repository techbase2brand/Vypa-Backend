<?php

namespace Marvel\Database\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use Sluggable;

    protected $table = 'employees';
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

    public $guarded = [];

    protected $casts = [
        'logo' => 'json',
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }



    /**
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'company_id');
    }

}
