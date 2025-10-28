<?php

use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('compte has correct fillable attributes', function () {
    $compte = new Compte();

    expect($compte->getFillable())->toBe([
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
    ]);
});

test('compte belongs to client', function () {
    $client = Client::factory()->create();
    $compte = Compte::factory()->create(['client_id' => $client->id]);

    expect($compte->client)->toBeInstanceOf(Client::class);
    expect($compte->client->id)->toBe($client->id);
});

test('compte has many transactions', function () {
    $compte = Compte::factory()->create();
    $transactions = Transaction::factory()->count(3)->create(['compte_id' => $compte->id]);

    expect($compte->transactions)->toHaveCount(3);
    expect($compte->transactions->first())->toBeInstanceOf(Transaction::class);
});

test('compte solde is calculated correctly', function () {
    $compte = Compte::factory()->create();

    // Create depot transaction
    Transaction::factory()->create([
        'compte_id' => $compte->id,
        'type' => 'Depot',
        'montant' => 1000,
        'statut' => 'Validee',
    ]);

    // Create retrait transaction
    Transaction::factory()->create([
        'compte_id' => $compte->id,
        'type' => 'Retrait',
        'montant' => 300,
        'statut' => 'Validee',
    ]);

    // Create pending transaction (should not affect solde)
    Transaction::factory()->create([
        'compte_id' => $compte->id,
        'type' => 'Depot',
        'montant' => 500,
        'statut' => 'En attente',
    ]);

    expect($compte->fresh()->solde)->toBe(700.0); // 1000 - 300
});

test('compte numeroCompte is auto-generated on creation', function () {
    $compte = Compte::factory()->create();

    expect($compte->numeroCompte)->toStartWith('CPT-' . date('Y') . '-');
    expect(strlen($compte->numeroCompte))->toBe(15); // CPT-2025-XXXXXX = 15 chars (Str::random(6) generates 6 chars)
});

test('compte has correct casts', function () {
    $compte = new Compte();

    $casts = $compte->getCasts();

    expect($casts['dateCreation'])->toBe('date');
    expect($casts['metadata'])->toBe('array');
});

test('compte isBlocked returns false for active account', function () {
    $compte = Compte::factory()->create(['statut' => 'Actif']);

    expect($compte->isBlocked())->toBeFalse();
});

test('compte isBlocked returns true for blocked account without dates', function () {
    $compte = Compte::factory()->create(['statut' => 'Bloque']);

    expect($compte->isBlocked())->toBeTrue();
});

test('compte isBlocked returns true for blocked account within blocking period', function () {
    $compte = Compte::factory()->create([
        'statut' => 'Bloque',
        'date_debut_blocage' => now()->subDays(1),
        'date_fin_blocage' => now()->addDays(1),
    ]);

    expect($compte->isBlocked())->toBeTrue();
});

test('compte isBlocked returns false for blocked account after blocking period', function () {
    $compte = Compte::factory()->create([
        'statut' => 'Bloque',
        'date_debut_blocage' => now()->subDays(2),
        'date_fin_blocage' => now()->subDays(1),
    ]);

    expect($compte->isBlocked())->toBeFalse();
});

test('compte isBlocked returns true for blocked account with only start date in past', function () {
    $compte = Compte::factory()->create([
        'statut' => 'Bloque',
        'date_debut_blocage' => now()->subDays(1),
    ]);

    expect($compte->isBlocked())->toBeTrue();
});

test('compte isBlocked returns false for blocked account with only start date in future', function () {
    $compte = Compte::factory()->create([
        'statut' => 'Bloque',
        'date_debut_blocage' => now()->addDays(1),
    ]);

    expect($compte->isBlocked())->toBeFalse();
});

test('compte isBlocked returns true for blocked account with only end date in future', function () {
    $compte = Compte::factory()->create([
        'statut' => 'Bloque',
        'date_fin_blocage' => now()->addDays(1),
    ]);

    expect($compte->isBlocked())->toBeTrue();
});

test('compte isBlocked returns false for blocked account with only end date in past', function () {
    $compte = Compte::factory()->create([
        'statut' => 'Bloque',
        'date_fin_blocage' => now()->subDays(1),
    ]);

    expect($compte->isBlocked())->toBeFalse();
});
