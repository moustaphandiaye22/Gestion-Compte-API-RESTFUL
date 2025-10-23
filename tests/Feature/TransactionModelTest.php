<?php

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transaction has correct fillable attributes', function () {
    $transaction = new Transaction();

    expect($transaction->getFillable())->toBe([
        'id',
        'numeroCompte',
        'type',
        'montant',
        'dateTransaction',
        'description',
        'statut',
        'compte_id',
    ]);
});

test('transaction belongs to compte', function () {
    $compte = Compte::factory()->create();
    $transaction = Transaction::factory()->create(['compte_id' => $compte->id]);

    expect($transaction->compte)->toBeInstanceOf(Compte::class);
    expect($transaction->compte->id)->toBe($compte->id);
});

test('transaction has correct casts', function () {
    $transaction = new Transaction();

    $casts = $transaction->getCasts();

    expect($casts['montant'])->toBe('decimal:2');
    expect($casts['dateTransaction'])->toBe('datetime');
});

test('transaction factory creates valid data', function () {
    $transaction = Transaction::factory()->create();

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->id)->toBeString();
    expect($transaction->numeroCompte)->toBeString();
    expect(in_array($transaction->type, ['Depot', 'Retrait', 'Virement']))->toBeTrue();
    expect(is_numeric($transaction->montant))->toBeTrue(); // Since it's cast to decimal, it might be string
    expect($transaction->dateTransaction)->toBeInstanceOf(\Carbon\Carbon::class);
    expect(in_array($transaction->statut, ['En attente', 'Validee', 'Annulee']))->toBeTrue();
    expect($transaction->compte_id)->toBeString();
});

test('transaction can be created with different types', function () {
    $compte = Compte::factory()->create();

    $depot = Transaction::factory()->create([
        'compte_id' => $compte->id,
        'type' => 'Depot',
        'montant' => 1000,
        'statut' => 'Validee',
    ]);

    $retrait = Transaction::factory()->create([
        'compte_id' => $compte->id,
        'type' => 'Retrait',
        'montant' => 500,
        'statut' => 'Validee',
    ]);

    $virement = Transaction::factory()->create([
        'compte_id' => $compte->id,
        'type' => 'Virement',
        'montant' => 200,
        'statut' => 'En attente',
    ]);

    expect($depot->type)->toBe('Depot');
    expect($retrait->type)->toBe('Retrait');
    expect($virement->type)->toBe('Virement');
});
