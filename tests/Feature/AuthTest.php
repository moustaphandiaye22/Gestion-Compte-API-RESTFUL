<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;
    protected $adminUser;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un admin et un client
        $this->admin = Admin::factory()->create();
        $this->client = Client::factory()->create();

        // Créer les utilisateurs associés
        $this->adminUser = User::factory()->create([
            'userable_type' => 'App\\Models\\Admin',
            'userable_id' => $this->admin->id,
        ]);

        $this->clientUser = User::factory()->create([
            'userable_type' => 'App\\Models\\Client',
            'userable_id' => $this->client->id,
        ]);
    }

    public function test_admin_can_login_successfully()
    {
        $response = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->adminUser->email,
            'password' => 'password', // Mot de passe par défaut des factories
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => [
                             'id',
                             'email',
                             'role'
                         ],
                         'access_token',
                         'token_type',
                         'expires_in'
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertEquals('admin', $data['user']['role']);
        $this->assertEquals('Bearer', $data['token_type']);
    }

    public function test_client_can_login_successfully()
    {
        $response = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->clientUser->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertEquals('client', $data['user']['role']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->adminUser->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['success' => false])
                 ->assertJson([
                     'error' => [
                         'code' => 'INVALID_CREDENTIALS'
                     ]
                 ]);
    }

    public function test_login_fails_with_invalid_email()
    {
        $response = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }

    public function test_refresh_token_works()
    {
        // D'abord se connecter
        $loginResponse = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Puis rafraîchir le token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
    ])->postJson('/ndiaye/v1/auth/refresh');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'access_token',
                         'token_type',
                         'expires_in'
                     ]
                 ]);
    }

    public function test_refresh_token_fails_without_auth()
    {
        $response = $this->postJson('/ndiaye/v1/auth/refresh');

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }

    public function test_logout_works()
    {
        // Se connecter
        $loginResponse = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Se déconnecter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/ndiaye/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Vérifier qu'une tentative d'utiliser le token révoqué échoue avec 401
        $this->get('/ndiaye/v1/auth/user', [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(401);
    }

    public function test_get_user_info_works()
    {
        // Se connecter
        $loginResponse = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Récupérer les infos utilisateur
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/ndiaye/v1/auth/user');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJson([
                     'data' => [
                         'id' => $this->adminUser->id,
                         'email' => $this->adminUser->email,
                         'role' => 'admin'
                     ]
                 ]);
    }

    public function test_get_user_info_fails_without_auth()
    {
    $response = $this->getJson('/ndiaye/v1/auth/user');

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }

    public function test_role_middleware_blocks_client_from_admin_routes()
    {
        // Se connecter en tant que client
        $loginResponse = $this->postJson('/ndiaye/v1/auth/login', [
            'email' => $this->clientUser->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Tenter d'accéder à une route admin (bloquer compte)
        // Le statut devrait être 403 (Forbidden) car le client est authentifié
        // mais n'a pas les permissions nécessaires
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/ndiaye/v1/comptes/1/bloquer');

        $response->assertStatus(403)
                 ->assertJson(['success' => false]);
    }
}