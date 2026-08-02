<?php

namespace App\Http\Requests\Auth;

use App\Actions\Auth\RedirectToGoogle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GoogleCallbackRequest extends FormRequest
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
            'code' => ['nullable', 'required_without:error', 'string', 'max:2048'],
            'error' => ['nullable', 'required_without:code', 'string', 'max:255'],
            'error_description' => ['nullable', 'string', 'max:2048'],
            'state' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator): void {
                if ($this->filled('code') && $this->filled('error')) {
                    $validator->errors()->add('code', 'The code and error fields are mutually exclusive.');
                }
            },
        ];
    }

    /**
     * Redirect malformed callbacks to the SPA instead of returning a JSON 422
     * page that the browser cannot interpret.
     */
    protected function failedValidation(Validator $validator): void
    {
        $frontendUrl = (string) config('app.frontend_url');

        throw new HttpResponseException(
            redirect()->away(
                $frontendUrl.'/auth/google/callback?error='.urlencode('GOOGLE_AUTH_FAILED')
            )->withoutCookie(
                RedirectToGoogle::FLOW_COOKIE,
                RedirectToGoogle::FLOW_COOKIE_PATH,
            )
        );
    }

    public function code(): string
    {
        return (string) $this->validated('code');
    }

    public function state(): string
    {
        return (string) $this->validated('state');
    }

    public function hasProviderError(): bool
    {
        return is_string($this->validated('error'));
    }
}
