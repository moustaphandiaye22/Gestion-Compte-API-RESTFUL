<?php

use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('compte has correct fillable attributes', function () {
    $compte = new Compte();

    expect($compte->getFillable())->toBe([
        'id',
        'numeroCompte',
        'titulaire',
        'type',
        'devise',
        'dateCreation',
        'statut',
        'metadata',
        'client_id',
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
