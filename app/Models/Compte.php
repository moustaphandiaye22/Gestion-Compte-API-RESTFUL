<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
           'numeroCompte',
           'titulaire',
           'type',
           'devise',
           'dateCreation',
           'statut',
           'metadata',
           'client_id',
           'dateFermeture',
           'date_debut_blocage',
           'date_fin_blocage',
           'motifBlocage',
       ];

    protected $casts = [
          'dateCreation' => 'date',
          'dateFermeture' => 'datetime',
          'date_debut_blocage' => 'datetime',
          'date_fin_blocage' => 'datetime',
          'metadata' => 'array',
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

    /**
     * Vérifie si le compte est actuellement bloqué.
     * Un compte est considéré bloqué si son statut est 'Bloque' et que la date actuelle
     * est comprise entre date_debut_blocage et date_fin_blocage (si ces dates sont définies).
     * Si les dates ne sont pas définies, le blocage est considéré permanent.
     */
    public function isBlocked()
    {
        if ($this->statut !== 'Bloque') {
            return false;
        }

        $now = now();

        // Si les dates de blocage sont définies, vérifier si on est dans la période
        if ($this->date_debut_blocage && $this->date_fin_blocage) {
            return $now->between($this->date_debut_blocage, $this->date_fin_blocage);
        }

        // Si seulement la date de début est définie, bloqué à partir de cette date
        if ($this->date_debut_blocage) {
            return $now->gte($this->date_debut_blocage);
        }

        // Si seulement la date de fin est définie, bloqué jusqu'à cette date
        if ($this->date_fin_blocage) {
            return $now->lte($this->date_fin_blocage);
        }

        // Si aucune date n'est définie, le blocage est permanent
        return true;
    }
}
