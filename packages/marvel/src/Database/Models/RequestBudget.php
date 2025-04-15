<?php

namespace Marvel\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestBudget extends Model
{
    protected $table = 'request_budgets';

    public $guarded = [];

    /**
     * @return BelongsToMany
     */
    // In your RequestBudget model
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
        // Assuming customer_id in request_budgets references id in employees
    }
}
