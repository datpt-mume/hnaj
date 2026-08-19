<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation cho POST /api/visits.
 */
class StoreVisitRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'source' => ['sometimes', 'nullable', 'string', 'max:30', 'in:discovery,detail,search,bookmarks,history'],
        ];
    }
}