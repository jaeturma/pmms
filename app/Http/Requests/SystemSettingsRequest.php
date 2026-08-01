<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `recaptcha_secret_key`/`smtp_password` are deliberately optional
     * even when the rest of their section is filled in — the edit page
     * never sends the real decrypted secret back to the browser (only a
     * `has_*` boolean), so a blank field here means "leave the stored
     * secret unchanged," not "clear it." Controller-level logic decides
     * that, not this request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'recaptcha_enabled' => ['required', 'boolean'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:255'],

            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],

            'email_verification_enabled' => ['required', 'boolean'],
        ];
    }
}
