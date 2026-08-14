<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cập nhật profile: chỉ `full_name` là writable; username/email/avatar read-only.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Endpoint yêu cầu xác thực; authorization được xử lý ở tầng route/middleware.
     */
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
            'full_name' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('full_name'))) {
            $this->merge(['full_name' => trim($this->input('full_name'))]);
        }
    }

    public function fullName(): string
    {
        return (string) $this->validated('full_name');
    }
}