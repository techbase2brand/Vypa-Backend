<?php

namespace Marvel\Database\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Marvel\Traits\TranslationTrait;

class Order extends Model
{
    use SoftDeletes;
    use TranslationTrait;


    protected $table = 'orders';

    public $guarded = [];

    protected $casts = [
        'shipping_address'    => 'json',
        'billing_address'     => 'json',
        'payment_intent_info' => 'json',
        'selectlogo'          => 'json',
        'logoUrl'             => 'json',
    ];

    protected $hidden = [
        //        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected static function boot()
    {
        parent::boot();
        // Order by created_at desc
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }

    protected $with = ['customer', 'products.variation_options','shop'];

    /**
     * @return belongsToMany
     */
    public function products(): belongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->using(OrderProduct::class) // Use the custom pivot model
            ->withPivot('order_quantity', 'unit_price', 'subtotal', 'variation_option_id', 'selectlogo','total_logo_cost','logoUrl','employee','employee_details')
            ->withTimestamps();
    }

    /**
     * @return belongsTo
     */
    public function coupon(): belongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * @return belongsTo
     */
    public function customer(): belongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany('Marvel\Database\Models\Order', 'parent_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function parent_order()
    {
        return $this->hasOne('Marvel\Database\Models\Order', 'id', 'parent_id');
    }

    /**
     * @return HasOne
     */
    public function refund()
    {
        return $this->hasOne(Refund::class, 'order_id');
    }
    /**
     * @return HasOne
     */
    public function wallet_point()
    {
        return $this->hasOne(OrderWalletPoint::class, 'order_id');
    }

    /**
     * @return HasMany
     */
    public function payment_intent()
    {
        return $this->hasMany(PaymentIntent::class);
    }

    /**
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
