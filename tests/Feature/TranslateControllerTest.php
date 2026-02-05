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
        expect($translation->value)->toBe('Original OK'); 
    });

    test('replaces existing translations when replace_existing is true', function () {
        Storage::fake('local');

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

        $enContent = json_encode(['common' => ['ok' => 'OK']]);
        $enFile = UploadedFile::fake()->createWithContent('en_US.json', $enContent);

        actingAs($this->user)->postJson('/api/translations/import', [
            'file' => $enFile,
            'lang' => 'en_US',
        ]);

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

        Translation::create([
            'platform' => 'web',
             'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);
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

describe('Translation Export', function () {
    beforeEach(function () {
        // Translation::query()->delete();
        $this->user = User::factory()->create();
    });

    test('can export translations for a lang', function () {
        // Create sample translations
        Translation::create([
            'platform' => 'web',
            'lang' => 'en_US',
            'group' => 'common',
            'key' => 'ok',
            'value' => 'OK',
        ]);

        Translation::create([
            'platform' => 'web',
            'lang' => 'en_US',
            'group' => 'common',
            'key' => 'cancel',
            'value' => 'Cancel',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'lang' => 'en_US',
            ])
            ->assertJsonStructure([
                'success',
                'lang',
                'data' => [
                    'common' => [
                        'ok',
                        'cancel',
                    ],
                ],
            ]);

        expect($response->json('data.common.ok'))->toBe('OK')
            ->and($response->json('data.common.cancel'))->toBe('Cancel');
    });

    test('exports translations with correct nested structure', function () {
        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'common',
            'key' => 'save',
            'value' => 'Save',
        ]);

        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'auth',
            'key' => 'login',
            'value' => 'Log In',
        ]);

        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'auth',
            'key' => 'logout',
            'value' => 'Log Out',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data)->toHaveKeys(['common', 'auth'])
            ->and($data['common'])->toHaveKey('save')
            ->and($data['auth'])->toHaveKeys(['login', 'logout'])
            ->and($data['common']['save'])->toBe('Save')
            ->and($data['auth']['login'])->toBe('Log In')
            ->and($data['auth']['logout'])->toBe('Log Out');
    });

    test('exports deeply nested translation groups', function () {
        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'validation.custom.email',
            'key' => 'required',
            'value' => 'Email is required',
        ]);

        Translation::create([
            'lang' => 'en_US',
            'group' => 'validation.custom.password',
            'platform' => 'web',
            'key' => 'min',
            'value' => 'Password must be at least 8 characters',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data)->toHaveKey('validation')
            ->and($data['validation'])->toHaveKey('custom')
            ->and($data['validation']['custom'])->toHaveKeys(['email', 'password'])
            ->and($data['validation']['custom']['email']['required'])->toBe('Email is required')
            ->and($data['validation']['custom']['password']['min'])->toBe('Password must be at least 8 characters');
    });

    test('returns 404 when no translations found for lang', function () {
        $response = actingAs($this->user)->getJson('/api/translations/export/fr_FR');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No translations found for lang: fr_FR',
            ]);
    });

    test('only exports translations for specified lang', function () {
        // Create English translations
        Translation::create([
            'lang' => 'en_US',
            'group' => 'common',
            'platform' => 'web',
            'key' => 'ok',
            'value' => 'OK',
        ]);

        // Create Spanish translations
        Translation::create([
            'lang' => 'es_ES',
            'group' => 'common',
            'platform' => 'web',
            'key' => 'ok',
            'value' => 'Aceptar',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200)
            ->assertJsonPath('lang', 'en_US')
            ->assertJsonPath('data.common.ok', 'OK');

        // Verify Spanish translation is not included
        $data = $response->json('data');
        expect($data['common']['ok'])->toBe('OK')
            ->and($data['common']['ok'])->not->toBe('Aceptar');
    });

    test('exports translations with null groups as top-level keys', function () {
        Translation::create([
            'lang' => 'en_US',
            'platform' => 'desktop',
            'group' => null,
            'key' => 'app_name',
            'value' => 'My Application',
        ]);

        Translation::create([
            'lang' => 'en_US',
            'group' => 'common',
            'platform' => 'desktop',
            'key' => 'ok',
            'value' => 'OK',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data)->toHaveKeys(['app_name', 'common'])
            ->and($data['app_name'])->toBe('My Application')
            ->and($data['common']['ok'])->toBe('OK');
    });

    test('handles large number of translations', function () {
        // Create 200 translations across different groups
        for ($i = 0; $i < 200; $i++) {
            Translation::create([
                'lang' => 'en_US',
                'group' => 'group_' . ($i % 10),
                'platform' => 'web',
                'key' => 'key_' . $i,
                'value' => 'Value ' . $i,
            ]);
        }

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify structure
        expect($data)->toHaveKey('group_0')
            ->and($data)->toHaveKey('group_9')
            ->and(count($data, COUNT_RECURSIVE) - count($data))->toBe(200); // Count all leaf nodes
    });

    test('preserves special characters in exported translations', function () {
        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'special',
            'key' => 'unicode',
            'value' => 'Hello 你好 مرحبا',
        ]);

        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'special',
            'key' => 'quotes',
            'value' => 'He said "Hello"',
        ]);

        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'special',
            'key' => 'apostrophe',
            'value' => "It's working",
        ]);

        Translation::create([
            'lang' => 'en_US',
            'platform' => 'web',
            'group' => 'special',
            'key' => 'html',
            'value' => '<strong>Bold</strong>',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data.special');

        expect($data['unicode'])->toBe('Hello 你好 مرحبا')
            ->and($data['quotes'])->toBe('He said "Hello"')
            ->and($data['apostrophe'])->toBe("It's working")
            ->and($data['html'])->toBe('<strong>Bold</strong>');
    });

    test('exports empty groups correctly', function () {
        Translation::create([
            'lang' => 'en_US',
            'group' => 'common',
            'platform' => 'web',
            'key' => 'ok',
            'value' => 'OK',
        ]);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data)->toHaveKey('common')
            ->and($data['common'])->toBeArray()
            ->and($data['common'])->toHaveKey('ok');
    });

    test('export matches original import structure', function () {
        // Create translations that match the import structure
        $originalData = [
            'common' => [
                'ok' => 'OK',
                'cancel' => 'Cancel',
                'save' => 'Save',
            ],
            'auth' => [
                'login' => 'Log In',
                'logout' => 'Log Out',
            ],
            'validation' => [
                'required' => 'This field is required',
                'email' => 'Invalid email',
            ],
        ];

        // Flatten and create translations
        foreach ($originalData as $group => $translations) {
            foreach ($translations as $key => $value) {
                Translation::create([
                    'lang' => 'en_US',
                    'group' => $group,
                    'key' => $key,
                    'platform' => 'web',
                    'value' => $value,
                ]);
            }
        }

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $exportedData = $response->json('data');

        expect($exportedData)->toEqual($originalData);
    });

    test('handles multiple langs independently on export', function () {
        // Create English translations
        Translation::create(['platform' => 'web','lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);
        Translation::create(['platform' => 'web','lang' => 'en_US', 'group' => 'common', 'key' => 'cancel', 'value' => 'Cancel']);

        // Create Spanish translations
        Translation::create(['platform' => 'web','lang' => 'es_ES', 'group' => 'common', 'key' => 'ok', 'value' => 'Aceptar']);
        Translation::create(['platform' => 'web','lang' => 'es_ES', 'group' => 'common', 'key' => 'cancel', 'value' => 'Cancelar']);

        // Export English
        $enResponse = actingAs($this->user)->getJson('/api/translations/export/en_US');
        $enResponse->assertStatus(200)
            ->assertJsonPath('lang', 'en_US')
            ->assertJsonPath('data.common.ok', 'OK')
            ->assertJsonPath('data.common.cancel', 'Cancel');

        // Export Spanish
        $esResponse = actingAs($this->user)->getJson('/api/translations/export/es_ES');
        $esResponse->assertStatus(200)
            ->assertJsonPath('lang', 'es_ES')
            ->assertJsonPath('data.common.ok', 'Aceptar')
            ->assertJsonPath('data.common.cancel', 'Cancelar');
    });

    test('exports translations in consistent order', function () {
        // Create translations in random order
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'z_group', 'key' => 'z_key', 'value' => 'Z Value']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'a_group', 'key' => 'a_key', 'value' => 'A Value']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify all groups are present
        expect($data)->toHaveKeys(['z_group', 'a_group', 'common']);
    });

    test('handles export with mixed group depths', function () {
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'simple', 'key' => 'key1', 'value' => 'Value 1']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'nested.level1', 'key' => 'key2', 'value' => 'Value 2']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'nested.level1.level2', 'key' => 'key3', 'value' => 'Value 3']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data)->toHaveKeys(['simple', 'nested'])
            ->and($data['simple']['key1'])->toBe('Value 1')
            ->and($data['nested']['level1']['key2'])->toBe('Value 2')
            ->and($data['nested']['level1']['level2']['key3'])->toBe('Value 3');
    });

    test('export produces valid JSON that can be re-imported', function () {
        // Create sample translations
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'cancel', 'value' => 'Cancel']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'auth', 'key' => 'login', 'value' => 'Log In']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $exportedData = $response->json('data');

        // Verify it's valid JSON structure
        $jsonString = json_encode($exportedData);
        expect($jsonString)->not->toBeFalse()
            ->and(json_decode($jsonString, true))->toEqual($exportedData);

        // Verify structure matches import format
        expect($exportedData)->toBeArray()
            ->and($exportedData)->toHaveKeys(['common', 'auth']);
    });

    test('returns correct response structure on successful export', function () {
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'lang',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'lang' => 'en_US',
            ]);

        expect($response->json('success'))->toBeTrue()
            ->and($response->json('lang'))->toBe('en_US')
            ->and($response->json('data'))->toBeArray();
    });

    test('handles lang parameter case sensitivity', function () {
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);

        // Should work with exact match
        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');
        $response->assertStatus(200);

        // Should not find with different case
        $response = actingAs($this->user)->getJson('/api/translations/export/EN_US');
        $response->assertStatus(404);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_us');
        $response->assertStatus(404);
    });

    test('exports single translation correctly', function () {
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'app', 'key' => 'name', 'value' => 'My App']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200)
            ->assertJsonPath('data.app.name', 'My App');

        $data = $response->json('data');
        expect($data)->toHaveKey('app')
            ->and($data['app'])->toHaveKey('name')
            ->and(count($data['app']))->toBe(1);
    });

    test('handles translations with numeric-like keys', function () {
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'errors', 'key' => '404', 'value' => 'Not Found']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'errors', 'key' => '500', 'value' => 'Server Error']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data['errors'])->toHaveKeys(['404', '500'])
            ->and($data['errors']['404'])->toBe('Not Found')
            ->and($data['errors']['500'])->toBe('Server Error');
    });

    test('exports translations with empty string values', function () {
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'empty', 'value' => '']);
        Translation::create(['platform' => 'web', 'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK']);

        $response = actingAs($this->user)->getJson('/api/translations/export/en_US');

        $response->assertStatus(200);

        $data = $response->json('data');

        expect($data['common']['empty'])->toBe('')
            ->and($data['common']['ok'])->toBe('OK');
    });

    test('export and re-import produces identical data', function () {
        // Create original translations
        $originalTranslations = [
            ['platform' => 'web' ,'lang' => 'en_US', 'group' => 'common', 'key' => 'ok', 'value' => 'OK'],
            ['platform' => 'web' ,'lang' => 'en_US', 'group' => 'common', 'key' => 'cancel', 'value' => 'Cancel'],
            ['platform' => 'web' ,'lang' => 'en_US', 'group' => 'auth', 'key' => 'login', 'value' => 'Log In'],
            ['platform' => 'web' ,'lang' => 'en_US', 'group' => 'validation.errors', 'key' => 'required', 'value' => 'Required field'],
        ];

        foreach ($originalTranslations as $translation) {
            Translation::create($translation);
        }

        // Export
        $exportResponse = actingAs($this->user)->getJson('/api/translations/export/en_US');
        $exportedData = $exportResponse->json('data');

        // Clear database
        Translation::query()->delete();

        // Verify database is empty
        expect(Translation::count())->toBe(0);

        // Re-import via controller (simulate)
        // In real scenario, you'd use the import endpoint
        // For this test, we'll manually recreate from exported structure
        recreateTranslationsFromExport($exportedData, 'web', 'en_US');

        // Verify count matches
        expect(Translation::count())->toBe(4);

        // Export again
        $reExportResponse = actingAs($this->user)->getJson('/api/translations/export/en_US');
        $reExportedData = $reExportResponse->json('data');

        // Compare data structures
        expect($reExportedData)->toEqual($exportedData);
    });
})->group('translation-export');

