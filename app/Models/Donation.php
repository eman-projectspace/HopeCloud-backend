<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Donation extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'submitted',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'condition',
        'location',
        'image',
        'quantity',
        'preferred_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'preferred_date' => 'date:Y-m-d',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}