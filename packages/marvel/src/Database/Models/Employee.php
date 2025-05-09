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
        'last_name',
        'email',
        'password',
        'company_name',
        'gender',
        'contact_no',
        'joining_date',
        'tag',
        'job_title',
        'logo',
        'address',
        'contact_info',
        'web',
        'date_of_birth',
        'shipping_address'
    ];

    public $guarded = [];

    protected $casts = [
        'logo' => 'json',
        'address' => 'json',
        'contact_info' => 'json',
        'shipping_address' => 'json'
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
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'customer_id', 'owner_id');
    }
    /**
     * Get orders associated with the employee's owner_id.
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'owner_id');
    }
}
