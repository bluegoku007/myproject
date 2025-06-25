<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city',
        'start_date',
        'end_date',
        'budget',
        'interests',
        'prompt',
        'recommendations',
    ];

    protected $casts = [
        'interests' => 'array', // Automatically casts JSON to array and back
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the user who owns the activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
