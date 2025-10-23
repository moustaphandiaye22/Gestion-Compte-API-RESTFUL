<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory;

    // Using default keyType and incrementing

    protected $fillable = [
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

        // Global scope pour comptes non supprimés
        static::addGlobalScope('nonSupprime', function ($builder) {
            $builder->where('comptes.statut', '!=', 'Supprime');
        });

        static::creating(function ($model) {
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
        $transfertSortant = $this->transactions()->where('type', 'Transfert')->where('statut', 'Validee')->sum('montant');
        return $depot - $retrait - $transfertSortant;
    }

    // Scope local pour récupérer un compte par numéro
    public function scopeNumero($query, $numero)
    {
        return $query->where('numeroCompte', $numero);
    }

    // Scope local pour récupérer les comptes d'un client basé sur le téléphone
    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }
}
