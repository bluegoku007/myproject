<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model {
    use HasFactory;
#siuu

    protected $fillable = [
        'origin',
        'destination',
        'from_date',
        'to_date',
        'adults',
        'budget',
        'selected_flight',
        'interests',
        'currency',
        'email',
    ];

    protected $casts = [
        'selected_flight' => 'array', // Ensure JSON is converted automatically
        'interests' => 'array',       // Ensure JSON is converted automatically
    ];
}
