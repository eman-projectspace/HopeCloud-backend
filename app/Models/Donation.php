<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Donation extends Model
{
    use HasFactory;

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
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}