<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    // Using default keyType and incrementing

    protected $fillable = [
         'prenom',
         'nom',
         'cni',
         'telephone',
         'date_creation',
     ];

    protected $casts = [
        'date_creation' => 'datetime',
    ];

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }
}
