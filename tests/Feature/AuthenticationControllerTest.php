<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

describe('Login', function () {
    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200);
        expect($response->json('token'))->not->toBeNull();
        assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-device',
        ]);
    });

    test('login requires email field', function () {
        $response = postJson('/api/login', [
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('login requires valid email format', function () {
        $response = postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('login requires password field', function () {
        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('login requires device name field', function () {
        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_name']);
    });

    test('login fails with incorrect email', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ]);
    });

    test('login fails with incorrect password', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ]);
    });

    test('multiple devices can have separate tokens', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Login from first device
        $response1 = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'device-1',
        ]);

        // Login from second device
        $response2 = postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'device-2',
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'device-1',
        ]);

        assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'device-2',
        ]);

        expect($user->tokens)->toHaveCount(2);
    });
});

describe('Logout', function () {
    test('authenticated user can logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User successfully logged out.'
            ]);

        assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    test('logout requires authentication', function () {
        $response = postJson('/api/logout');

        $response->assertStatus(401);
    });

    test('logout only deletes current device tokens', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create tokens for two devices
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        // Logout from first device
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/logout');

        $response->assertStatus(200);

        // First device token should be deleted
        expect($user->fresh()->tokens)->toHaveCount(1);
    });
});

describe('Register', function () {
    test('user can register with valid data', function () {
        $userData = [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        expect(Hash::check('password123', $user->password))->toBeTrue();
    });

    test('register requires all fields', function () {
        $response = postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    test('register requires valid email format', function () {
        $response = postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('register requires unique email', function () {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('register requires password confirmation', function () {
        $response = postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('register creates token for new user', function () {
        $response = postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'newuser@example.com')->first();
        assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });
});