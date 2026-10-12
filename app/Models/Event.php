<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'location',
        'event_date',
        'capacity',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
    public function activeRegistration()
    {
        return $this->hasMany(Registration::class)->whereIn('status', ['registered', 'attended']);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
