<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompteApiTest extends TestCase
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
        $this->adminToken = $adminUser->createToken('admin-token')->plainTextToken;
        $this->clientToken = $clientUser->createToken('client-token')->plainTextToken;

        // Créer des comptes de test
        Compte::factory()->count(5)->create([
            'client_id' => $this->client->id,
            'type' => 'Epargne',
            'statut' => 'Actif',
        ]);

        Compte::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'type' => 'Cheque',
            'statut' => 'Actif',
        ]);
    }

    public function test_admin_can_list_all_comptes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'numeroCompte',
                            'titulaire',
                            'type',
                            'solde',
                            'devise',
                            'dateCreation',
                            'statut',
                            'metadata',
                        ]
                    ],
                    'pagination' => [
                        'currentPage',
                        'totalPages',
                        'totalItems',
                        'itemsPerPage',
                        'hasNext',
                        'hasPrevious',
                    ],
                    'links' => [
                        'self',
                        'next',
                        'previous',
                        'first',
                        'last',
                    ],
                ])
                ->assertJson(['success' => true]);
    }

    public function test_client_can_list_only_his_comptes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->clientToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes');

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(8, $data); // 5 Epargne + 3 Cheque

        // Vérifier que tous les comptes appartiennent au client
        foreach ($data as $compte) {
            $this->assertEquals($this->client->id, $compte['client']['id']);
        }
    }

    public function test_filter_by_type_epargne()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes?type=Epargne');

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $data = $response->json('data');
        foreach ($data as $compte) {
            $this->assertEquals('Epargne', $compte['type']);
        }
    }

    public function test_filter_by_statut_actif()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes?statut=Actif');

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $data = $response->json('data');
        foreach ($data as $compte) {
            $this->assertEquals('Actif', $compte['statut']);
        }
    }

    public function test_pagination_works()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes?page=1&limit=5');

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(5, $data);

        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['currentPage']);
        $this->assertEquals(5, $pagination['itemsPerPage']);
    }

    public function test_sort_by_date_creation_desc()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes?sort=dateCreation&order=desc');

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(count($data), 8); // Au moins 8 éléments pour admin
        if (count($data) > 1) {
            // Vérifier que les dates sont en ordre décroissant
            for ($i = 0; $i < count($data) - 1; $i++) {
                $this->assertGreaterThanOrEqual($data[$i]['dateCreation'], $data[$i + 1]['dateCreation']);
            }
        }
    }

    public function test_unauthenticated_request_succeeds()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/ndiaye/v1/comptes');

        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }

    public function test_create_compte_successfully()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'devise' => 'FCFA',
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(201)
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'numeroCompte',
                        'titulaire',
                        'type',
                        'solde',
                        'devise',
                        'dateCreation',
                        'statut',
                        'metadata'
                    ]
                ]);
    }

    public function test_create_compte_fails_with_invalid_type()
    {
        $data = [
            'type' => 'InvalidType',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    public function test_create_compte_fails_with_invalid_solde_initial()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 5000, // Less than 10000
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['soldeInitial']);
    }

    public function test_create_compte_fails_with_invalid_devise()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'devise' => 'VeryLongCurrencyNameThatExceedsTenCharacters',
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['devise']);
    }

    public function test_create_compte_fails_with_invalid_client_id()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'id' => 'invalid-uuid',
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.id']);
    }

    public function test_create_compte_fails_with_invalid_titulaire()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => '', // Empty
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.titulaire']);
    }

    public function test_create_compte_fails_with_invalid_nci()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1234567890123', // Does not start with 19 or 20
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567', // Valid telephone
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.nci']);
    }

    public function test_create_compte_fails_with_invalid_email()
    {
        // First create a client with an email
        Client::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'existing@example.com', // Duplicate
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.email']);
    }

    public function test_create_compte_fails_with_invalid_telephone()
    {
        // First create a client with a telephone
        Client::factory()->create(['telephone' => '+221771234567']);

        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567', // Duplicate
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.telephone']);
    }

    public function test_create_compte_fails_with_invalid_telephone_pattern()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221991234567', // Invalid operator
                'adresse' => 'Dakar, Senegal'
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.telephone']);
    }

    public function test_create_compte_fails_with_invalid_adresse()
    {
        $data = [
            'type' => 'Cheque',
            'soldeInitial' => 50000,
            'client' => [
                'titulaire' => 'John Doe',
                'nci' => '1990123456789',
                'email' => 'john.doe@example.com',
                'telephone' => '+221771234567',
                'adresse' => '' // Empty
            ]
        ];

        $response = $this->postJson('/ndiaye/v1/comptes', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['client.adresse']);
    }
}
