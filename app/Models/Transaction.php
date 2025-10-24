<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

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

        // Generate UUID for primary key
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
