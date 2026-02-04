<?php

use App\Models\Translation;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('Translation CRUD Operataions', function(){
    beforeEach(function () {
        $this->user = User::factory()->create();
    });


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

    test('it can list translations with key filter', function () {
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
            ->getJson('/api/translations?lang=en&key=World');

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
            ->getJson('/api/translations?lang=en&platform=web&key=World');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'lang' => 'en',
            'platform' => 'web',
            'value' => 'Hello World'
        ]);
    });

    test('it can show a single translation', function () {
        $translation = Translation::factory()->create([
            'lang' => 'en',
            'key' => 'greeting',
            'value' => 'Hello World'
        ]);

        $response = actingAs($this->user)
            ->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $translation->id,
            'lang' => 'en',
            'key' => 'greeting',
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
            'key' => 'greeting',
            'value' => 'Hello World',
            'platform' => 'web'
        ];

        $response = actingAs($this->user)
            ->postJson('/api/translations', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Successfully Created new Translation',
            'key' => 'greeting'
        ]);
        
        assertDatabaseHas('translations', [
            'lang' => 'en',
            'key' => 'greeting',
            'value' => 'Hello World',
            'platform' => 'web'
        ]);
    });

    test('it validates required fields on create', function () {
        $response = actingAs($this->user)
            ->postJson('/api/translations', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lang', 'key', 'value']);
    });

    // Update Tests
    test('it can update a translation', function () {
        $translation = Translation::factory()->create([
            'lang' => 'en',
            'key' => 'greeting',
            'value' => 'Hello World'
        ]);

        $updateData = [
            'key' => 'farewell',
            'value' => 'Goodbye World'
        ];

        $response = actingAs($this->user)
            ->putJson("/api/translations/{$translation->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => "Successfully Updated Translation for {$updateData['key']}",
            'key' => 'farewell',
            'old_translation' => 'Hello World',
            'new_translation' => 'Goodbye World'
        ]);

        assertDatabaseHas('translations', [
            'id' => $translation->id,
            'key' => 'farewell',
            'value' => 'Goodbye World'
        ]);
    });

    test('it validates required fields on update', function () {
        $translation = Translation::factory()->create();

        $response = actingAs($this->user)
            ->putJson("/api/translations/{$translation->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key', 'value']);
    });

    // Delete Tests
    test('it can delete a translation', function () {
        $translation = Translation::factory()->create([
            'key' => 'test_key'
        ]);

        $response = actingAs($this->user)
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => "Successfully Deleted Translation for {$translation->key}"
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
            'key' => 'greeting',
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
            'key' => 'greeting',
            'value' => 'Hello World',
            'platform' => $platform
        ];

        $response = actingAs($this->user)
            ->postJson('/api/translations', $data);

        $response->assertStatus(200);
        assertDatabaseHas('translations', ['platform' => $platform]);
    })->with(['web', 'mobile', 'desktop']);
});

describe('Translation Import', function () {
    beforeEach(function () {
        // Clean up translations before each test
        // Translation::query()->delete();
        $this->user = User::factory()->create();
    });

    test('can import valid JSON translation file', function () {
        Storage::fake('local');

        $jsonContent = json_encode([
            'common' => [
                'ok' => 'OK',
                'cancel' => 'Cancel',
                'save' => 'Save',
            ],
            'auth' => [
                'login' => 'Log In',
                'logout' => 'Log Out',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('en_US.json', $jsonContent);

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Translations imported successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'lang',
                    'imported',
                    'updated',
                    'skipped',
                    'total',
                ],
            ]);

        expect(Translation::count())->toBe(5)
            ->and(Translation::where('lang', 'en_US')->count())->toBe(5)
            ->and(Translation::where('group', 'common')->count())->toBe(3)
            ->and(Translation::where('group', 'auth')->count())->toBe(2);
    });

    test('imports translations with correct data structure', function () {
        Storage::fake('local');

        $jsonContent = json_encode([
            'common' => [
                'ok' => 'OK',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('en_US.json', $jsonContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $translation = Translation::first();

        expect($translation->lang)->toBe('en_US')
            ->and($translation->group)->toBe('common')
            ->and($translation->key)->toBe('ok')
            ->and($translation->value)->toBe('OK');
    });

    test('handles nested translation groups correctly', function () {
        Storage::fake('local');

        $jsonContent = json_encode([
            'validation' => [
                'errors' => [
                    'required' => 'This field is required',
                    'email' => 'Invalid email',
                ],
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('en_US.json', $jsonContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        expect(Translation::count())->toBe(2);

        $requiredTranslation = Translation::where('key', 'required')->first();
        expect($requiredTranslation->group)->toBe('validation.errors')
            ->and($requiredTranslation->value)->toBe('This field is required');
    });

    test('skips existing translations when replace_existing is false', function () {
        Storage::fake('local');

        // Create existing translation
        Translation::create([
            'lang' => 'en_US',
            'group' => 'common',
            'key' => 'ok',
            'platform' => 'web',
            'value' => 'Original OK',
        ]);

        $jsonContent = json_encode([
            'common' => [
                'ok' => 'New OK',
                'cancel' => 'Cancel',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('en_US.json', $jsonContent);

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
            'replace_existing' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.imported', 1)
            ->assertJsonPath('data.skipped', 1);

        $translation = Translation::where('key', 'ok')->first();
        expect($translation->value)->toBe('Original OK'); // Should remain unchanged
    });

    test('replaces existing translations when replace_existing is true', function () {
        Storage::fake('local');

        // Create existing translation
        Translation::create([
            'lang' => 'en_US',
            'group' => 'common',
            'key' => 'ok',
            'platform' => 'web',
            'value' => 'Original OK',
        ]);

        $jsonContent = json_encode([
            'common' => [
                'ok' => 'Updated OK',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('en_US.json', $jsonContent);

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
            'replace_existing' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.imported', 0);

        $translation = Translation::where('key', 'ok')->first();
        expect($translation->value)->toBe('Updated OK');
    });

    // test('deletes old translations for lang when replace_existing is true', function () {
    //     Storage::fake('local');

    //     // Create existing translations
    //     Translation::create([
    //         'lang' => 'en_US',
    //         'group' => 'common',
    //         'key' => 'old_key',
    //         'platform' => 'web', 
    //         'value' => 'Old Value',
    //     ]);

    //     Translation::create([
    //         'lang' => 'es_ES',
    //         'group' => 'common',
    //         'key' => 'spanish_key',
    //         'platform' => 'web',
    //         'value' => 'Spanish Value',
    //     ]);

    //     $jsonContent = json_encode([
    //         'common' => [
    //             'new_key' => 'New Value',
    //         ],
    //     ]);

    //     $file = UploadedFile::fake()->createWithContent('en_US.json', $jsonContent);

    //     actingAs($this->user)->postJson('/api/translations/import', [
    //         'file' => $file,
    //         'lang' => 'en_US',
    //         'replace_existing' => true,
    //     ]);

    //     expect(Translation::where('lang', 'en_US')->count())->toBe(1)
    //         ->and(Translation::where('lang', 'es_ES')->count())->toBe(1)
    //         ->and(Translation::where('key', 'old_key')->exists())->toBeFalse()
    //         ->and(Translation::where('key', 'new_key')->exists())->toBeTrue();
    // });

    test('validates file is required', function () {
        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'lang' => 'en_US',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    });

    test('validates lang is required', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('test.json', '{}');

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lang']);
    });

    test('validates lang format', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('test.json', '{}');

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'invalid-lang',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lang']);
    });

    test('accepts valid lang formats', function ($lang) {
        Storage::fake('local');

        $jsonContent = json_encode(['test' => ['key' => 'value']]);
        $file = UploadedFile::fake()->createWithContent('test.json', $jsonContent);

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => $lang,
        ]);

        $response->assertStatus(200);
    })->with([
        'en_US',
        'es_ES',
        'fr_FR',
        'de_DE',
        'ja_JP',
    ]);

    test('validates file must be JSON', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    });

    test('rejects invalid JSON content', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('invalid.json', 'not valid json{]');

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ])
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'Invalid JSON'));
    });


    test('rejects empty JSON file', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('empty.json', json_encode([]));

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'No valid translations found in the file.',
            ]);
    });

    test('handles large translation files', function () {
        Storage::fake('local');

        $translations = [];
        for ($i = 0; $i < 100; $i++) {
            $translations["group{$i}"] = [
                "key{$i}" => "Value {$i}",
            ];
        }

        $file = UploadedFile::fake()->createWithContent('large.json', json_encode($translations));

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 100);

        expect(Translation::count())->toBe(100);
    });

    test('handles special characters in translations', function () {
        Storage::fake('local');

        $jsonContent = json_encode([
            'special' => [
                'unicode' => 'Hello 你好 مرحبا',
                'quotes' => 'He said "Hello"',
                'apostrophe' => "It's working",
                'html' => '<strong>Bold</strong>',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('special.json', $jsonContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $unicode = Translation::where('key', 'unicode')->first();
        $quotes = Translation::where('key', 'quotes')->first();
        $apostrophe = Translation::where('key', 'apostrophe')->first();
        $html = Translation::where('key', 'html')->first();

        expect($unicode->value)->toBe('Hello 你好 مرحبا')
            ->and($quotes->value)->toBe('He said "Hello"')
            ->and($apostrophe->value)->toBe("It's working")
            ->and($html->value)->toBe('<strong>Bold</strong>');
    });

    test('maintains data integrity with database transactions', function () {
        Storage::fake('local');

        // Create a translation that will cause a duplicate key error on second import
        Translation::create([
            'lang' => 'en_US',
            'group' => 'common',
            'key' => 'ok',
            'platform' => 'web',
            'value' => 'OK',
        ]);

        $jsonContent = json_encode([
            'common' => [
                'ok' => 'OK Updated',
                'cancel' => 'Cancel',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('test.json', $jsonContent);

        // Import with replace_existing = false, which should skip the duplicate
        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
            'replace_existing' => false,
        ]);

        // Should have 2 translations total (1 original + 1 new)
        expect(Translation::count())->toBe(2);
    });

    test('imports multiple langs independently', function () {
        Storage::fake('local');

        // Import English
        $enContent = json_encode(['common' => ['ok' => 'OK']]);
        $enFile = UploadedFile::fake()->createWithContent('en_US.json', $enContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $enFile,
            'lang' => 'en_US',
        ]);

        // Import Spanish
        $esContent = json_encode(['common' => ['ok' => 'Aceptar']]);
        $esFile = UploadedFile::fake()->createWithContent('es_ES.json', $esContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $esFile,
            'lang' => 'es_ES',
        ]);

        expect(Translation::where('lang', 'en_US')->count())->toBe(1)
            ->and(Translation::where('lang', 'es_ES')->count())->toBe(1)
            ->and(Translation::count())->toBe(2);

        $enTranslation = Translation::where('lang', 'en_US')->first();
        $esTranslation = Translation::where('lang', 'es_ES')->first();

        expect($enTranslation->value)->toBe('OK')
            ->and($esTranslation->value)->toBe('Aceptar');
    });

    test('returns correct statistics after import', function () {
        Storage::fake('local');

        // Create some existing translations
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'cancel', 'value' => 'Cancel']);

        $jsonContent = json_encode([
            'common' => [
                'ok' => 'OK Updated',
                'cancel' => 'Cancel Updated',
                'save' => 'Save',
                'delete' => 'Delete',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('test.json', $jsonContent);

        $response = actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
            'replace_existing' => true,
        ]);


        $response->assertStatus(200)
            ->assertJsonPath('data.lang', 'en_US')
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.updated', 2)  
            ->assertJsonPath('data.skipped', 0)
            ->assertJsonPath('data.total', 4);
    });

    test('handles deeply nested translation groups', function () {
        Storage::fake('local');

        $jsonContent = json_encode([
            'validation' => [
                'custom' => [
                    'email' => [
                        'required' => 'Email is required',
                    ],
                ],
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('test.json', $jsonContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $file,
            'lang' => 'en_US',
        ]);

        $translation = Translation::first();

        expect($translation->group)->toBe('validation.custom.email')
            ->and($translation->key)->toBe('required')
            ->and($translation->value)->toBe('Email is required');
    });
});