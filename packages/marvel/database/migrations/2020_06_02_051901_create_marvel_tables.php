<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Marvel\Enums\CouponType;
use Marvel\Enums\OrderStatus;
use Marvel\Enums\PaymentStatus;
use Marvel\Enums\ProductStatus;
use Marvel\Enums\ProductType;
use Marvel\Enums\ShippingType;

class CreateMarvelTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('amount');
            $table->string('is_global')->default(true);
            $table->enum('type', ShippingType::getValues())->default(ShippingType::FIXED);
            $table->timestamps();
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->text('description')->nullable();
            $table->json('image')->nullable();
            $table->enum('type', CouponType::getValues())->default(CouponType::DEFAULT_COUPON);
            $table->float('amount')->default(0);
            $table->float('minimum_cart_amount')->default(0);
            $table->string('active_from');
            $table->string('expire_at');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->json('promotional_sliders')->nullable();
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_approved')->default(false);
            $table->json('image')->nullable();
            $table->json('cover_image')->nullable();
            $table->string('slug');
            $table->text('bio')->nullable();
            $table->text('quote')->nullable();
            $table->string('born')->nullable();
            $table->string('death')->nullable();
            $table->string('languages')->nullable();
            $table->json('socials')->nullable();
            $table->timestamps();
        });

        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_approved')->default(false);
            $table->json('image')->nullable();
            $table->json('cover_image')->nullable();
            $table->string('slug');
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->json('socials')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('cascade');
            $table->double('price')->nullable();
            $table->double('sale_price')->nullable();
            $table->string('sku')->nullable();
            $table->integer('quantity')->default(0);
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_taxable')->default(false);
            $table->unsignedBigInteger('shipping_class_id')->nullable();
            $table->foreign('shipping_class_id')->references('id')->on('shipping_classes');
            $table->enum('status', ProductStatus::getValues())->default(ProductStatus::DRAFT);
            //$table->enum('product_type', ProductType::getValues())->default(ProductType::SIMPLE);
            //$table->string('unit');
            $table->string('height')->nullable();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->json('image')->nullable();
            $table->json('gallery')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_contact');
            $table->string('customer_name')->nullable();
            $table->double('amount');
            $table->double('sales_tax')->nullable();
            $table->double('paid_total')->nullable();
            $table->double('total')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->double('discount')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('altered_payment_gateway')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->unsignedBigInteger('logistics_provider')->nullable();
            $table->double('delivery_fee')->nullable();
            $table->string('delivery_time')->nullable();
            $table->enum('order_status', OrderStatus::getValues())->default(OrderStatus::DEFAULT_ORDER_STATUS);
            $table->enum('payment_status', PaymentStatus::getValues())->default(PaymentStatus::DEFAULT_PAYMENT_STATUS);
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('users');
        });

        Schema::create('order_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('order_quantity');
            $table->double('unit_price');
            $table->double('subtotal');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->json('image')->nullable();
            $table->text('details')->nullable();
            $table->unsignedBigInteger('parent')->nullable();
            $table->foreign('parent')->references('id')->on('categories')->onDelete('cascade');
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->unsignedBigInteger('attribute_id');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('attribute_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_value_id');
            $table->foreign('attribute_value_id')->references('id')->on('attribute_values')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->double('rate');
            $table->string('name')->nullable();
            $table->integer('is_global')->nullable();
            $table->integer('priority')->nullable();
            $table->boolean('on_shipping')->default(1);
            $table->timestamps();
        });

        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->boolean('default')->default(false);
            $table->json('address');
            $table->json('location')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->json('options');
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->json('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->json('socials')->nullable();
            $table->string('contact')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(1);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('url')->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_classes');
        Schema::dropIfExists('shipping_classes');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('types');
        Schema::dropIfExists('products');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_product');
        Schema::dropIfExists('tax_classes');
        Schema::dropIfExists('address');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('manufacturers');
    }
}
