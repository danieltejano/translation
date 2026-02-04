<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{
    CreateTranslationRequest,
    UpdateTranslationRequest,
};
use App\Models\Translation;
use App\Http\Resources\TranslationResource;

class TranslateController extends Controller
{
    public function index(Request $request){
        $translations = Translation::when($request->query('lang'), function($query) use($request){
            $query->whereLike('lang', $request->query('lang'));
        })
        ->when($request->query('platform'), function($query) use ($request){
            $query->whereIn('platform', [$request->query('platform')]);
        })
        ->when($request->query('purpose'), function($query) use($request){
            $search_term = $request->query('purpose');
            $query->whereLike('value', "%$search_term%")
                    ->OrWhereLike('purpose', "%$search_term%");
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
            'purpose' => $translation->purpose, 
            'translation' => $translation->value, 
        ]);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation){
        $fields = $request->validated();

        $original_translation = $translation->getAttributes();

        $translation->update($fields);

        return response()->json([
            'message' => "Successfully Updated Translation for $translation->purpose",
            'purpose' => $translation->purpose,
            'old_translation' => $original_translation['value'],
            'new_translation' => $translation->value
        ]);
    }

    public function delete(Translation $translation){
        $translation->delete();

        return response()->json([
            'message' => "Successfully Deleted Translation for $translation->purpose"
        ]);
    }
    
}
