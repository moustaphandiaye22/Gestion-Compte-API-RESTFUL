<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    // Using default keyType and incrementing

    protected $fillable = [
         'nom',
         'poste',
         'date_creation',
     ];

    protected $casts = [
        'date_creation' => 'datetime',
    ];

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }
}
