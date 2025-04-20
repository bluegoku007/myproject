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
        Schema::create('country_capitals', function (Blueprint $table) {
            $table->id();
            $table->string('country')->unique(); // Country name (e.g., "France")
            $table->string('country_code', 2)->nullable(); // ISO country code (e.g., "FR")
            $table->string('capital'); // Capital city name (e.g., "Paris")
            $table->string('iata_code', 3); // IATA code (e.g., "PAR")
            $table->string('source')->default('local'); // Source of the data
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index('country');
            $table->index('country_code');
            $table->index('iata_code');
        });

        // Optional: Seed initial data
        DB::table('country_capitals')->insert([
            [
                'country' => 'Tunisia',
                'country_code' => 'TN',
                'capital' => 'Tunis',
                'iata_code' => 'TUN',
                'source' => 'local',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'country' => 'France',
                'country_code' => 'FR',
                'capital' => 'Paris',
                'iata_code' => 'PAR',
                'source' => 'local',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Add more countries as needed
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_capitals');
    }
};