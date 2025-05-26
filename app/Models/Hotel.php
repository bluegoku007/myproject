<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hotel_id',
        'name',
        'city',
        'country',
        'check_in',
        'check_out',
        'price',
        'currency',
        'details',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'price' => 'decimal:2',
        'details' => 'array',
    ];

    /**
     * Get the user that owns the hotel entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
