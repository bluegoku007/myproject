<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin', 'destination', 'fromDate', 'toDate', 'adults', 'budget', 'selectedFlight','interests', // ✅ Ajout des intérêts
    ];

    protected $casts = [
        'selectedFlight' => 'json',
        'interests' => 'array',
    ];
}
