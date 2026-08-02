<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Endpoint công khai, authorization được xử lý ở tầng route.
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
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                // Chỉ cho phép chữ thường, số, dấu chấm và gạch dưới để username
                // hiển thị nhất quán và không lẫn với địa chỉ email.
                'regex:/^[a-z0-9._]+$/',
                'not_regex:/^[._]|[._]$/',
                'unique:users,username',
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'The username may only contain lowercase letters, numbers, dots and underscores.',
            'username.not_regex' => 'The username must not start or end with a dot or underscore.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => mb_strtolower(trim($this->input('username')))]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array{username: string, full_name: string, email: string, password: string}
     */
    public function registrationData(): array
    {
        return [
            'username' => (string) $this->validated('username'),
            'full_name' => (string) $this->validated('full_name'),
            'email' => (string) $this->validated('email'),
            'password' => (string) $this->validated('password'),
        ];
    }
}
