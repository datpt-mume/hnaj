<?php

namespace App\Http\Requests\ManagerApplication;

use Illuminate\Foundation\Http\FormRequest;

class SubmitManagerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'proof_reference' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
