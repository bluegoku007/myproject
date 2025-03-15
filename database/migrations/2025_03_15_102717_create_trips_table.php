<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('origin');
            $table->string('destination');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('adults');
            $table->decimal('budget', 10, 2);
            $table->json('selected_flight'); // Stores flight details as JSON
            $table->json('interests')->nullable(); // Stores selected interests
            $table->string('currency')->default('USD');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('trips');
    }
};
