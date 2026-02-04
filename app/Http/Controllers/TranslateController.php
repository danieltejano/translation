<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    CreateTranslationRequest,
    UpdateTranslationRequest,
};
use App\Models\Translation;
use App\Http\Resources\TranslationResource;
use App\TranslationParserTrait;

class TranslateController extends Controller
{
    use TranslationParserTrait;

    public function index(Request $request){
        $translations = Translation::when($request->query('lang'), function($query) use($request){
            $query->whereLike('lang', $request->query('lang'));
        })
        ->when($request->query('platform'), function($query) use ($request){
            $query->whereIn('platform', [$request->query('platform')]);
        })
        ->when($request->query('key'), function($query) use($request){
            $search_term = $request->query('key');
            $query->whereLike('value', "%$search_term%")
                    ->OrWhereLike('key', "%$search_term%");
        })->paginate(15);

        return $translations->toResourceCollection();
    }

    public function show(Translation $translation){
        return new TranslationResource($translation);
    }

    public function create(CreateTranslationRequest $request){
        $fields = $request->validated();

        $translation = Translation::create($fields);

        return response()->json([
            'message' => 'Successfully Created new Translation',
            'key' => $translation->key, 
            'translation' => $translation->value, 
        ]);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation){
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

    public function delete(Translation $translation){
        $translation->delete();

        return response()->json([
            'message' => "Successfully Deleted Translation for $translation->key"
        ]);
    }
    
    public function import(Request $request)
    {
        $file = $request->file('translation');
        $language = $request->input('lang');
        $platform = $request->input('platform');

        $file_path = file_get_contents($file->getRealPath());
        $json_content = json_decode($file_path, true);

        // Should validte JSON Structure here
        // Should validate JSON contains an array

        $translations = $this->parseFile($json_content, $platform, $language);

        // Should check if parsed file is empty.
        
        foreach($translations as $translation){
            Translation::create($translation);
        }

    
    }

    
}
