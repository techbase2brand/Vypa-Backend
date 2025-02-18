<?php

namespace Marvel\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class CompanySetting extends Model
{

    protected $table = 'company_setting';

    public $guarded = [];


    protected $casts = [
        'logo' => 'json',
    ];


    /**
     * @return BelongsToMany
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
