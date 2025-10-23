<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'numeroCompte',
        'titulaire',
        'type',
        'devise',
        'dateCreation',
        'statut',
        'metadata',
        'client_id',
    ];

    protected $casts = [
        'dateCreation' => 'date',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            if (empty($model->numeroCompte)) {
                // Exemple : CPT-2025-XXXXX
                $model->numeroCompte = 'CPT-' . date('Y') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getSoldeAttribute()
    {
        $depot = $this->transactions()->where('type', 'Depot')->where('statut', 'Validee')->sum('montant');
        $retrait = $this->transactions()->where('type', 'Retrait')->where('statut', 'Validee')->sum('montant');
        return $depot - $retrait;
    }
}
