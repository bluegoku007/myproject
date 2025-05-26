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
  // database/migrations/xxxx_create_hotels_table.php
Schema::create('hotels', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('hotel_id');
    $table->string('name');
    $table->string('city');
    $table->string('country');
    $table->date('check_in');
    $table->date('check_out');
    $table->decimal('price', 10, 2);
    $table->string('currency');
    $table->json('details'); // Entire API response
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
