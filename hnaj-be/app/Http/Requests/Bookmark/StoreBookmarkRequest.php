<?php

namespace App\Http\Requests\Bookmark;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation cho POST /api/bookmarks.
 */
class StoreBookmarkRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'place_id' => ['required', 'integer', 'exists:places,id'],
        ];
    }
}
