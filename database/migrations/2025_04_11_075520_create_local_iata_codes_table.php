<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('local_iata_codes', function (Blueprint $table) {
            $table->id();
            $table->string('city')->unique();
            $table->string('iata_code', 3);  // Changed from 'iata' to match the schema
            $table->string('country')->nullable();  // Added country column
            $table->timestamps();
        });

        // Seed initial data
        DB::table('local_iata_codes')->insert([
            ['city' => 'Tunis', 'iata_code' => 'TUN', 'country' => 'Tunisia'],
            ['city' => 'Paris', 'iata_code' => 'PAR', 'country' => 'France'],
            ['city' => 'London', 'iata_code' => 'LHR', 'country' => 'United Kingdom'],
            ['city' => 'New York', 'iata_code' => 'JFK', 'country' => 'United States'],
            ['city' => 'Tokyo', 'iata_code' => 'TYO', 'country' => 'Japan'],
            ['city' => 'Dubai', 'iata_code' => 'DXB', 'country' => 'United Arab Emirates'],
            ['city' => 'Rome', 'iata_code' => 'FCO', 'country' => 'Italy'],
            ['city' => 'Madrid', 'iata_code' => 'MAD', 'country' => 'Spain'],
            ['city' => 'Berlin', 'iata_code' => 'BER', 'country' => 'Germany'],
            ['city' => 'Cairo', 'iata_code' => 'CAI', 'country' => 'Egypt'],
            // Add more cities as needed
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_iata_codes');
    }
};