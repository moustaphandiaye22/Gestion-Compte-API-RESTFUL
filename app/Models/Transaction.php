<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    // Using default keyType and incrementing

    protected $fillable = [
         'numeroCompte',
         'type',
         'montant',
         'dateTransaction',
         'description',
         'statut',
         'compte_id',
     ];

    protected $casts = [
        'dateTransaction' => 'datetime',
        'montant' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // No UUID generation needed
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
