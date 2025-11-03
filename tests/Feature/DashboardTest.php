<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;
    protected $adminToken;
    protected $clientToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un admin et un client
        $this->admin = Admin::factory()->create();
        $this->client = Client::factory()->create();

        // Créer les utilisateurs associés
        $adminUser = User::factory()->create([
            'userable_type' => 'App\\Models\\Admin',
            'userable_id' => $this->admin->id,
        ]);

        $clientUser = User::factory()->create([
            'userable_type' => 'App\\Models\\Client',
            'userable_id' => $this->client->id,
        ]);

        // Créer des tokens
        $this->adminToken = $adminUser->createToken('admin-token')->accessToken;
        $this->clientToken = $clientUser->createToken('client-token')->accessToken;

        // Créer des comptes de test
        $comptes = Compte::factory()->count(5)->create([
            'client_id' => $this->client->id,
            'type' => 'Epargne',
            'statut' => 'Actif',
        ]);

        // Créer des transactions de test
        foreach ($comptes as $compte) {
            // Dépôts
            Transaction::factory()->create([
                'compte_id' => $compte->id,
                'type' => 'Depot',
                'montant' => 100000,
                'statut' => 'Validee',
            ]);

            // Retraits
            Transaction::factory()->create([
                'compte_id' => $compte->id,
                'type' => 'Retrait',
                'montant' => 25000,
                'statut' => 'Validee',
            ]);

            // Transferts sortants
            Transaction::factory()->create([
                'compte_id' => $compte->id,
                'type' => 'Transfert',
                'montant' => 15000,
                'statut' => 'Validee',
            ]);
        }

        // Créer des comptes créés aujourd'hui
        Compte::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'type' => 'Cheque',
            'statut' => 'Actif',
            'dateCreation' => now(),
        ]);
    }

    public function test_admin_can_access_dashboard()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'totalComptes',
                        'balanceTotale',
                        'nombreTransactions',
                        'dernieresTransactions',
                        'comptesCreesAujourdhui',
                    ]
                ])
                ->assertJson(['success' => true]);
    }

    public function test_client_can_access_own_dashboard()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'nombreComptes',
                        'balanceTotale',
                        'nombreTransactions',
                        'dernieresTransactions',
                        'comptes',
                    ]
                ])
                ->assertJson(['success' => true]);
    }

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(401);
    }

    public function test_admin_dashboard_returns_correct_total_comptes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(8, $data['totalComptes']); // 5 Epargne + 3 Cheque
    }

    public function test_client_dashboard_returns_correct_account_count()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(8, $data['nombreComptes']); // All accounts belong to this client
    }

    public function test_dashboard_returns_correct_balance_totale()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        // 5 comptes * (100000 dépôt - 25000 retrait - 15000 transfert) = 5 * 60000 = 300000
        $this->assertEquals(300000, $data['balanceTotale']);
    }

    public function test_dashboard_returns_correct_transaction_count()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        // 5 comptes * 3 transactions chacun = 15 transactions
        $this->assertEquals(15, $data['nombreTransactions']);
    }

    public function test_dashboard_returns_last_10_transactions()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(10, $data['dernieresTransactions']);

        // Vérifier la structure des transactions
        foreach ($data['dernieresTransactions'] as $transaction) {
            $this->assertArrayHasKey('id', $transaction);
            $this->assertArrayHasKey('numeroCompte', $transaction);
            $this->assertArrayHasKey('type', $transaction);
            $this->assertArrayHasKey('montant', $transaction);
            $this->assertArrayHasKey('dateTransaction', $transaction);
            $this->assertArrayHasKey('description', $transaction);
        }
    }

    public function test_admin_dashboard_returns_today_created_accounts()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(3, $data['comptesCreesAujourdhui']);

        // Vérifier la structure des comptes
        foreach ($data['comptesCreesAujourdhui'] as $compte) {
            $this->assertArrayHasKey('id', $compte);
            $this->assertArrayHasKey('numeroCompte', $compte);
            $this->assertArrayHasKey('titulaire', $compte);
            $this->assertArrayHasKey('type', $compte);
            $this->assertArrayHasKey('dateCreation', $compte);
        }
    }

    public function test_client_dashboard_returns_client_accounts()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/dashboard');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(8, $data['comptes']); // All accounts belong to this client

        // Vérifier la structure des comptes
        foreach ($data['comptes'] as $compte) {
            $this->assertArrayHasKey('id', $compte);
            $this->assertArrayHasKey('numeroCompte', $compte);
            $this->assertArrayHasKey('titulaire', $compte);
            $this->assertArrayHasKey('type', $compte);
            $this->assertArrayHasKey('solde', $compte);
            $this->assertArrayHasKey('dateCreation', $compte);
        }
    }
}
