<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('email')->unique();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('contact_no');
            $table->string('password');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // Owner foreign key
            $table->timestamp('joining_date');
            $table->string('job_title');
            $table->string('tag');
            $table->json('logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
