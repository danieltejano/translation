<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => 'required',
            'group' => 'nullable',
            'lang' => 'nullable|regex:/^[a-z]{2}(_[A-Z]{2})?$/i', 
            'value' => 'required', 
            'platform' => 'nullable|in:mobile,web,desktop'
        ];
    }
}
