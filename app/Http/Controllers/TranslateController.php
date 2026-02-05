<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\{
    CreateTranslationRequest,
    ImportTranslationRequest,
    ExportTranslationRequest,
    UpdateTranslationRequest,
};
use App\Models\Translation;
use App\Http\Resources\TranslationResource;
use App\TranslationParserTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranslateController extends Controller
{
    use TranslationParserTrait;

    public function index(Request $request)
    {
        $translations = Translation::when($request->query('lang'), function ($query) use ($request) {
            $search_term = $request->query('lang', '');
            $query->whereLike('lang', "%$search_term%");
        })
        ->when($request->query('group'), function ($query) use ($request) {
            $search_term = $request->query('group');
            $query->whereLike('group', "%$search_term%");
        })
        ->when($request->query('platform'), function ($query) use ($request) {
            $platforms = explode(',', $request->query('platform'));
            $query->where(function ($inner_query) use ($platforms) {
                foreach ($platforms as $platform) {
                    $inner_query->orWhereJsonContains('platform', $platform);
                }
            });
        })
        ->when($request->query('key'), function ($query) use ($request) {
            $search_term = $request->query('key');
            $query->where(function ($inner_query) use ($search_term) {
                $inner_query->whereLike('value', "%$search_term%")
                    ->OrWhereLike('key', "%$search_term%");
            });
        })
        ->when($request->query('value'), function ($query) use ($request) {
            $search_term = $request->query('value');
            $query->whereLike('value', "%$search_term%");
        })->paginate($request->query('per_page', 15));


        return $translations->toResourceCollection();
    }

    public function show(Translation $translation)
    {
        return new TranslationResource($translation);
    }

    public function create(CreateTranslationRequest $request)
    {
        $fields = $request->validated();

        $translation = Translation::create([
            ...$fields,
            'platform' => json_encode([$fields['platform']])
        ]);

        return response()->json([
            'message' => 'Successfully Created new Translation',
            'key' => $translation->key,
            'translation' => $translation->value,
        ]);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation)
    {
        $fields = $request->validated();

        $original_translation = $translation->getAttributes();

        $translation->update($fields);

        return response()->json([
            'message' => "Successfully Updated Translation for $translation->key",
            'key' => $translation->key,
            'old_translation' => $original_translation['value'],
            'new_translation' => $translation->value
        ]);
    }

    public function delete(Translation $translation)
    {
        $translation->delete();

        return response()->json([
            'message' => "Successfully Deleted Translation for $translation->key"
        ]);
    }

    public function import(ImportTranslationRequest $request)
    {
        $fields = $request->validated();


        $file = $request->file('file');
        $language = $request->input('lang');
        $platform = $request->input('platform');
        $policy = $request->input('replace_existing', false);

        // $file_path = file_get_contents($file->getRealPath());
        $json_content = json_decode($file->get(), true);

        // Should validte JSON Structure here
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON File ' . json_last_error_msg()
            ], 422);
        }


        // for quick fix purposes platform is coalesced with web
        $translations = $this->parseFile($json_content, $platform ?? 'web', $language);

        // Should check if parsed file is empty.

        if (empty($translations)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid translations found in the file.'
            ], 422);
        }

        $policy_string = $policy ? 'true' : 'false';
        Log::info("Begining Translation with Replace Existing Policy set to $policy_string");

        DB::beginTransaction();

        try {
            $imported = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($translations as $translation) {
                $existing = Translation::where('lang', $translation['lang'])
                                        ->where('key', $translation['key'])
                                        ->first();
                $key = $translation['key'];
                $value = $translation['value'];
                $lang = $translation['lang'];
                if ($existing) {
                    if ($policy) {
                        $existing->update(['value' => $translation['value']]);
                        Log::info("Updated Translation $key for $lang with value $value");
                        $updated++;
                    } else {
                        Log::alert("Skipping Translation $key for $lang with value $value");
                        $skipped++;
                    }
                } else {
                    Translation::create($translation);
                    Log::info("Created Translation $key for $lang with value $value");
                    $imported++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Translations imported successfully.',
                'data' => [
                    'lang' => $language,
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'total' => count($translations)
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollback();

            throw $e;
        }
    }

    public function export(Request $request, string $lang)
    {
        $translations = Translation::where('lang', $lang)->get();

        if ($translations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No translations found for lang: $lang"
            ], 404);
        }

        $json_content = json_encode($this->groupTranslations($translations, !$request->query('flatten', false)));
        $file_name = "locale_$lang.json";
        Storage::put($file_name, $json_content);

        if ($request->query('download_file', false)) {
            return Storage::download("$file_name", now()->timestamp . '_' . $file_name);
        }

        return response()->json([
            'success' => true,
            'lang' => $lang,
            'data' => $this->groupTranslations($translations, !$request->query('flatten', false)),
            'timestamp' => now()->format('d/m/Y H:i:s')
        ], 200);
    }
}
