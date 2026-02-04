<?php

use App\Models\Translation;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\assertDeleted;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

// Index Tests
test('it can list translations with language filter', function () {
    Translation::factory()->create([
        'lang' => 'en',
        'value' => 'Hello World'
    ]);
    Translation::factory()->create([
        'lang' => 'es',
        'value' => 'Hola Mundo'
    ]);
    Translation::factory()->create([
        'lang' => 'en',
        'value' => 'Goodbye'
    ]);

    $response = actingAs($this->user)
        ->getJson('/api/translations?lang=en');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonFragment(['lang' => 'en']);
});

test('it can list translations with purpose filter', function () {
    Translation::factory()->create([
        'lang' => 'en',
        'value' => 'Hello World'
    ]);
    Translation::factory()->create([
        'lang' => 'en',
        'value' => 'Goodbye World'
    ]);
    Translation::factory()->create([
        'lang' => 'en',
        'value' => 'Test Message'
    ]);

    $response = actingAs($this->user)
        ->getJson('/api/translations?lang=en&purpose=World');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

test('it can filter by platform', function () {
    Translation::factory()->create([
        'lang' => 'en',
        'platform' => 'web'
    ]);
    Translation::factory()->create([
        'lang' => 'en',
        'platform' => 'mobile'
    ]);

    $response = actingAs($this->user)
        ->getJson('/api/translations?platform=web');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['platform' => 'web']);
});

test('it paginates results', function () {
    Translation::factory()->count(20)->create(['lang' => 'en']);

    $response = actingAs($this->user)
        ->getJson('/api/translations?lang=en');

    $response->assertStatus(200);
    $response->assertJsonCount(15, 'data');
    $response->assertJsonStructure([
        'data',
        'links',
        'meta'
    ]);
});

test('it can handle complex filtering', function () {
    Translation::factory()->create([
        'lang' => 'en',
        'platform' => 'web',
        'value' => 'Hello World'
    ]);
    Translation::factory()->create([
        'lang' => 'en',
        'platform' => 'mobile',
        'value' => 'Hello Mobile'
    ]);
    Translation::factory()->create([
        'lang' => 'es',
        'platform' => 'web',
        'value' => 'Hola Mundo'
    ]);

    $response = actingAs($this->user)
        ->getJson('/api/translations?lang=en&platform=web&purpose=World');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment([
        'lang' => 'en',
        'platform' => 'web',
        'value' => 'Hello World'
    ]);
});

// Show Tests
test('it can show a single translation', function () {
    $translation = Translation::factory()->create([
        'lang' => 'en',
        'purpose' => 'greeting',
        'value' => 'Hello World'
    ]);

    $response = actingAs($this->user)
        ->getJson("/api/translations/{$translation->id}");

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'id' => $translation->id,
        'lang' => 'en',
        'purpose' => 'greeting',
        'value' => 'Hello World'
    ]);
});

test('it returns 404 when translation not found', function () {
    $response = actingAs($this->user)
        ->getJson('/api/translations/99999');

    $response->assertStatus(404);
});

// Create Tests
test('it can create a translation', function () {
    $data = [
        'lang' => 'en',
        'purpose' => 'greeting',
        'value' => 'Hello World',
        'platform' => 'web'
    ];

    $response = actingAs($this->user)
        ->postJson('/api/translations', $data);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Successfully Created new Translation',
        'purpose' => 'greeting'
    ]);
    
    assertDatabaseHas('translations', [
        'lang' => 'en',
        'purpose' => 'greeting',
        'value' => 'Hello World',
        'platform' => 'web'
    ]);
});

test('it validates required fields on create', function () {
    $response = actingAs($this->user)
        ->postJson('/api/translations', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lang', 'purpose', 'value']);
});

// Update Tests
test('it can update a translation', function () {
    $translation = Translation::factory()->create([
        'lang' => 'en',
        'purpose' => 'greeting',
        'value' => 'Hello World'
    ]);

    $updateData = [
        'purpose' => 'farewell',
        'value' => 'Goodbye World'
    ];

    $response = actingAs($this->user)
        ->putJson("/api/translations/{$translation->id}", $updateData);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => "Successfully Updated Translation for {$updateData['purpose']}",
        'purpose' => 'farewell',
        'old_translation' => 'Hello World',
        'new_translation' => 'Goodbye World'
    ]);

    assertDatabaseHas('translations', [
        'id' => $translation->id,
        'purpose' => 'farewell',
        'value' => 'Goodbye World'
    ]);
});

test('it validates required fields on update', function () {
    $translation = Translation::factory()->create();

    $response = actingAs($this->user)
        ->putJson("/api/translations/{$translation->id}", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['purpose', 'value']);
});

// Delete Tests
test('it can delete a translation', function () {
    $translation = Translation::factory()->create([
        'purpose' => 'test_purpose'
    ]);

    $response = actingAs($this->user)
        ->deleteJson("/api/translations/{$translation->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'message' => "Successfully Deleted Translation for {$translation->purpose}"
    ]);

    assertDatabaseMissing('translations', [
        'id' => $translation->id
    ]);
});

// Authentication Tests
test('it requires authentication', function () {
    $response = getJson('/api/translations');

    $response->assertStatus(401);
});

// Dataset examples (optional but useful for Pest)
test('it validates translation language codes', function (string $lang, bool $shouldPass) {
    $data = [
        'lang' => $lang,
        'purpose' => 'greeting',
        'value' => 'Hello World',
        'platform' => 'web'
    ];

    $response = actingAs($this->user)
        ->postJson('/api/translations', $data);

    if ($shouldPass) {
        $response->assertStatus(200);
    } else {
        $response->assertStatus(422);
    }
})->with([
    ['en', true],
    ['es', true],
    ['fr', true],
    ['', false],
    ['invalid-lang', false],
]);

test('it handles different platforms', function (string $platform) {
    $data = [
        'lang' => 'en',
        'purpose' => 'greeting',
        'value' => 'Hello World',
        'platform' => $platform
    ];

    $response = actingAs($this->user)
        ->postJson('/api/translations', $data);

    $response->assertStatus(200);
    assertDatabaseHas('translations', ['platform' => $platform]);
})->with(['web', 'mobile', 'desktop']);