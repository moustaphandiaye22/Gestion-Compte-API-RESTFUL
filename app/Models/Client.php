<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'uuid';

    protected $fillable = [
          'prenom',
          'nom',
          'cni',
          'telephone',
          'email',
          'adresse',
      ];

    protected $casts = [
         'id' => 'string',
     ];



    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    public function getTitulaireAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
